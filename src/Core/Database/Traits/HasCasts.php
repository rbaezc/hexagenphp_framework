<?php
namespace HexaGen\Core\Database\Traits;

trait HasCasts
{
    protected array $casts = [];
    protected array $hidden = [];
    protected array $visible = [];
    protected array $appends = [];

    public function getAttribute(string $key): mixed
    {
        $value = $this->$key ?? null;
        return $this->castAttribute($key, $value);
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->$key = $this->castForStorage($key, $value);
    }

    protected function castAttribute(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key]) || $value === null) {
            return $value;
        }

        return match ($this->casts[$key]) {
            'int', 'integer'    => (int) $value,
            'float', 'double'   => (float) $value,
            'string'            => (string) $value,
            'bool', 'boolean'   => (bool) $value,
            'array'             => is_string($value) ? (json_decode($value, true) ?? []) : (array) $value,
            'object'            => is_string($value) ? json_decode($value) : (object) $value,
            'json'              => is_string($value) ? (json_decode($value, true) ?? []) : $value,
            'date'              => $this->asDate($value),
            'datetime'          => $this->asDateTime($value),
            'timestamp'         => $this->asTimestamp($value),
            'encrypted'         => $this->decryptCast($value),
            'collection'        => \HexaGen\Core\Support\Collection::make(
                is_string($value) ? (json_decode($value, true) ?? []) : (array) $value
            ),
            default             => $value,
        };
    }

    protected function castForStorage(string $key, mixed $value): mixed
    {
        if (!isset($this->casts[$key]) || $value === null) {
            return $value;
        }

        return match ($this->casts[$key]) {
            'array', 'object', 'json', 'collection' =>
                is_string($value) ? $value : json_encode($value),
            'encrypted' => $this->encryptCast($value),
            'date', 'datetime' =>
                $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : $value,
            default => $value,
        };
    }

    protected function asDate(mixed $value): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) return $value;
        if ($value instanceof \DateTime) return \DateTimeImmutable::createFromMutable($value);
        return new \DateTimeImmutable((string) $value);
    }

    protected function asDateTime(mixed $value): \DateTimeImmutable
    {
        return $this->asDate($value);
    }

    protected function asTimestamp(mixed $value): int
    {
        if ($value instanceof \DateTimeInterface) return $value->getTimestamp();
        if (is_numeric($value)) return (int) $value;
        return (new \DateTimeImmutable((string) $value))->getTimestamp();
    }

    protected function encryptCast(mixed $value): string
    {
        $key   = base64_decode((string) getenv('APP_KEY'));
        $iv    = random_bytes(12);
        $tag   = '';
        $enc   = openssl_encrypt((string) $value, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        return base64_encode($iv . $tag . $enc);
    }

    protected function decryptCast(string $value): string
    {
        $key  = base64_decode((string) getenv('APP_KEY'));
        $raw  = base64_decode($value);
        $iv   = substr($raw, 0, 12);
        $tag  = substr($raw, 12, 16);
        $enc  = substr($raw, 28);
        return (string) openssl_decrypt($enc, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    }

    public function toArray(): array
    {
        $data = get_object_vars($this);

        // Apply casts
        foreach (array_keys($this->casts) as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->castAttribute($key, $data[$key]);
            }
        }

        // Apply appends (computed attributes via getXxxAttribute())
        foreach ($this->appends as $key) {
            $method = 'get' . str_replace('_', '', ucwords($key, '_')) . 'Attribute';
            if (method_exists($this, $method)) {
                $data[$key] = $this->$method();
            }
        }

        // Remove hidden fields
        if (!empty($this->hidden)) {
            $data = array_diff_key($data, array_flip($this->hidden));
        }

        // Apply visible filter
        if (!empty($this->visible)) {
            $data = array_intersect_key($data, array_flip($this->visible));
        }

        // Remove internal trait properties
        unset($data['casts'], $data['hidden'], $data['visible'], $data['appends']);

        return $data;
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode($this->toArray(), $flags);
    }

    public function makeHidden(array|string $attributes): static
    {
        $this->hidden = array_merge($this->hidden, (array) $attributes);
        return $this;
    }

    public function makeVisible(array|string $attributes): static
    {
        $this->visible = array_merge($this->visible, (array) $attributes);
        $this->hidden  = array_diff($this->hidden, (array) $attributes);
        return $this;
    }
}
