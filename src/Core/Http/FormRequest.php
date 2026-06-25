<?php
namespace HexaGen\Core\Http;

use Symfony\Component\HttpFoundation\Request;
use HexaGen\Core\Validation\Validator;
use HexaGen\Core\Validation\ValidationException;

abstract class FormRequest
{
    protected array $data = [];
    protected array $errors = [];

    public function __construct(private Request $request)
    {
        $contentType = $request->headers->get('Content-Type', '');
        if (str_contains($contentType, 'application/json')) {
            $this->data = json_decode($request->getContent(), true) ?: [];
        } else {
            $this->data = array_merge($request->query->all(), $request->request->all(), $request->files->all());
        }
    }

    abstract public function rules(): array;

    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    public function validate(): void
    {
        $validator = new Validator();
        if (!$validator->validate($this->all(), $this->rules(), $this->messages(), $this->attributes())) {
            throw new ValidationException($validator->getErrors());
        }
    }

    public function all(): array
    {
        return $this->data;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function only(array $keys): array
    {
        return array_intersect_key($this->data, array_flip($keys));
    }

    public function except(array $keys): array
    {
        return array_diff_key($this->data, array_flip($keys));
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    public function filled(string $key): bool
    {
        return !empty($this->data[$key]);
    }

    public function safe(): array
    {
        // Return only fields that have validation rules
        return $this->only(array_keys($this->rules()));
    }

    public function request(): Request
    {
        return $this->request;
    }

    public function user(): ?\HexaGen\Core\Auth\Authenticatable
    {
        return \HexaGen\Core\Auth\AuthManager::user();
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data[$name]);
    }
}
