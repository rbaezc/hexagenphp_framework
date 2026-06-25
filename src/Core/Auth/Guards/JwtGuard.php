<?php
namespace HexaGen\Core\Auth\Guards;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use HexaGen\Core\Auth\Authenticatable;
use HexaGen\Core\Cache\CacheManager;
use HexaGen\Core\Config;
use Symfony\Component\HttpFoundation\Request;

class JwtGuard
{
    private ?Authenticatable $resolved = null;
    private const ALGO = 'HS256';

    public function issueToken(Authenticatable $user): string
    {
        $secret = Config::get('auth.guards.jwt.secret');
        $ttl    = Config::get('auth.guards.jwt.ttl', 3600);

        if (!$secret) {
            throw new \RuntimeException('JWT_SECRET no está configurado en config/auth.php o en el entorno.');
        }

        $payload = [
            'sub' => $user->getAuthId(),
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + $ttl,
            'jti' => bin2hex(random_bytes(16)), // unique token ID for revocation
        ];

        return JWT::encode($payload, $secret, self::ALGO);
    }

    /** Revoke a token by blacklisting its jti in cache until expiry. */
    public function revoke(string $token): void
    {
        $decoded = $this->decode($token);
        if ($decoded && isset($decoded->jti, $decoded->exp)) {
            $ttl = max(0, $decoded->exp - time());
            CacheManager::set('jwt:blacklist:' . $decoded->jti, true, $ttl);
        }
    }

    public function user(Request $request): ?Authenticatable
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $token = $this->extractToken($request);
        if (!$token) {
            return null;
        }

        $decoded = $this->decode($token);
        if (!$decoded) {
            return null;
        }

        // Check blacklist (revoked tokens)
        if (isset($decoded->jti) && CacheManager::has('jwt:blacklist:' . $decoded->jti)) {
            return null;
        }

        $this->resolved = $this->findById($decoded->sub);
        return $this->resolved;
    }

    public function check(Request $request): bool { return $this->user($request) !== null; }
    public function guest(Request $request): bool  { return !$this->check($request); }

    public function reset(): void { $this->resolved = null; }

    private function decode(string $token): ?\stdClass
    {
        $secret = Config::get('auth.guards.jwt.secret');
        if (!$secret) {
            return null;
        }
        try {
            return JWT::decode($token, new Key($secret, self::ALGO));
        } catch (ExpiredException | SignatureInvalidException | \Exception) {
            return null;
        }
    }

    private function extractToken(Request $request): ?string
    {
        $header = $request->headers->get('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }
        return null;
    }

    private function findById(int|string $id): ?Authenticatable
    {
        $modelClass = Config::get('auth.providers.users.model');
        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }
        return $modelClass::find($id);
    }
}
