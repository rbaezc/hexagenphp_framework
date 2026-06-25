<?php
namespace HexaGen\Core\Database;

use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Resultado de una consulta paginada.
 * Se serializa a JSON automáticamente con metadata estándar.
 */
class Paginator
{
    public function __construct(
        private array  $data,
        private int    $total,
        private int    $perPage,
        private int    $currentPage,
    ) {}

    public function items(): array  { return $this->data; }
    public function total(): int    { return $this->total; }
    public function perPage(): int  { return $this->perPage; }
    public function currentPage(): int { return $this->currentPage; }
    public function lastPage(): int { return (int)ceil($this->total / $this->perPage); }
    public function hasMore(): bool { return $this->currentPage < $this->lastPage(); }

    public function toArray(): array
    {
        return [
            'data'          => $this->data,
            'meta' => [
                'total'        => $this->total,
                'per_page'     => $this->perPage,
                'current_page' => $this->currentPage,
                'last_page'    => $this->lastPage(),
                'has_more'     => $this->hasMore(),
            ],
        ];
    }

    public function toJson(): JsonResponse
    {
        return new JsonResponse($this->toArray());
    }
}
