<?php
namespace HexaGen\Core\Http;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Transforma un modelo en una representación JSON controlada.
 * Extiende esta clase y sobreescribe toArray() para definir qué campos exponer.
 *
 * Uso:
 *   class UserResource extends ApiResource {
 *       public function toArray(): array {
 *           return ['id' => $this->id, 'name' => $this->name];
 *       }
 *   }
 *
 *   return (new UserResource($user))->response();
 *   return UserResource::collection($users)->response();
 */
abstract class ApiResource
{
    public function __construct(protected mixed $resource) {}

    abstract public function toArray(): array;

    public function response(int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse(['data' => $this->toArray()], $status, $headers);
    }

    public static function collection(array $resources): ResourceCollection
    {
        return new ResourceCollection($resources, static::class);
    }

    /** Magic getter — accede a propiedades del modelo directamente desde el resource. */
    public function __get(string $name): mixed
    {
        return $this->resource->$name ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->resource->$name);
    }
}
