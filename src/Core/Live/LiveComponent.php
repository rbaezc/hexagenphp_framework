<?php
namespace HexaGen\Core\Live;

use HexaGen\Core\Template\TemplateEngine;

abstract class LiveComponent
{
    private string $id;

    public function __construct()
    {
        $this->id = 'live-' . uniqid();
    }

    private static function getAppKey(): string
    {
        $key = getenv('APP_KEY');
        if (!$key) {
            throw new \RuntimeException('APP_KEY environment variable is not set. Set it to a random 32+ character string.');
        }
        // Derive a fixed 32-byte key for AES-256
        return hash('sha256', $key, true);
    }

    /**
     * Retrieve all public properties (state) of the component.
     */
    public function getState(): array
    {
        $properties = get_object_vars($this);
        // Exclude internal component framework fields
        unset($properties['id']);
        return $properties;
    }

    /**
     * Properties that cannot be set via HTTP request input (mass assignment protection).
     * Override in subclasses to protect sensitive fields.
     * Example: protected array $guarded = ['isAdmin', 'role'];
     */
    protected array $guarded = [];

    /**
     * Hydrate component from trusted server-generated state (decrypted payload).
     */
    public function hydrate(array $state): void
    {
        foreach ($state as $key => $value) {
            if (property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Hydrate component from untrusted HTTP request input.
     * Respects the $guarded array — guarded properties cannot be overwritten.
     */
    public function hydrateFromInput(array $input): void
    {
        foreach ($input as $key => $value) {
            if (property_exists($this, $key) && !in_array($key, $this->guarded, true)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * Encrypt the current component state with AES-256-GCM.
     */
    public function getSignedState(): string
    {
        $key = self::getAppKey();
        $plaintext = json_encode($this->getState());
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $ciphertext);
    }

    /**
     * Decrypt and verify the AES-256-GCM encrypted state token.
     */
    public static function decryptState(string $payload): ?array
    {
        $key = self::getAppKey();
        $decoded = base64_decode($payload, true);
        // Minimum: 12 bytes IV + 16 bytes GCM tag
        if ($decoded === false || strlen($decoded) < 28) {
            return null;
        }

        $iv         = substr($decoded, 0, 12);
        $tag        = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);

        $plaintext = openssl_decrypt($ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            return null; // Decryption or authentication failed
        }

        return json_decode($plaintext, true);
    }

    /**
     * Render the template and wrap it in the LiveComponent container with state payload.
     */
    protected function renderView(string $template, array $extraData = []): string
    {
        $engine = new TemplateEngine();
        
        // Combine model state and custom variables
        $data = array_merge($this->getState(), $extraData);
        $html = $engine->render($template, $data);

        $parts = explode('\\', static::class);
        $componentName = end($parts);

        $stateToken = $this->getSignedState();

        // Wrap the HTML with HTMX endpoints and target variables
        return sprintf(
            '<div id="%s" data-live-component="%s" data-live-state="%s" hx-target="this" hx-swap="outerHTML">%s</div>',
            $this->id,
            $componentName,
            $stateToken,
            $html
        );
    }

    abstract public function render(): string;
}
