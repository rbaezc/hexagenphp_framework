<?php
namespace HexaGen\Core\Http;

use HexaGen\Core\Database\Paginator;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Colección de ApiResources. Soporta arrays simples y Paginators.
 */
class ResourceCollection
{
    private ?Paginator $paginator = null;

    public function __construct(
        private array  $items,
        private string $resourceClass,
    ) {}

    public static function fromPaginator(Paginator $paginator, string $resourceClass): self
    {
        $collection = new self($paginator->items(), $resourceClass);
        $collection->paginator = $paginator;
        return $collection;
    }

    public function response(int $status = 200, array $headers = []): JsonResponse
    {
        $data = array_map(
            fn($item) => (new $this->resourceClass($item))->toArray(),
            $this->items
        );

        $body = ['data' => $data];

        if ($this->paginator !== null) {
            $body['meta'] = $this->paginator->toArray()['meta'];
        }

        return new JsonResponse($body, $status, $headers);
    }
}
