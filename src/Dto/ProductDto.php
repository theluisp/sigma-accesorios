<?php

namespace App\Dto;

/**
 * Producto del catálogo, ya combinado entre todas las sucursales donde se vende.
 * El "slug" (columna interna de Rappi) es la llave estable entre sucursales.
 */
final class ProductDto
{
    /** @var BranchStock[] */
    private array $branches = [];

    public function __construct(
        public readonly string $slug,
        public readonly string $nombre,
        public readonly string $descripcion,
    ) {
    }

    public function addBranch(BranchStock $branch): void
    {
        $this->branches[] = $branch;
    }

    /** @return BranchStock[] */
    public function getBranches(): array
    {
        return $this->branches;
    }

    public function isDisponible(): bool
    {
        foreach ($this->branches as $branch) {
            if ($branch->disponible && $branch->stock > 0) {
                return true;
            }
        }

        return false;
    }

    public function getStockTotal(): int
    {
        return array_sum(array_map(static fn (BranchStock $b) => $b->stock, $this->branches));
    }

    /**
     * Precio de referencia a mostrar en el catálogo (el más bajo entre sucursales disponibles).
     */
    public function getPrecioDesde(): ?float
    {
        $precios = array_map(
            static fn (BranchStock $b) => $b->precio,
            array_filter($this->branches, static fn (BranchStock $b) => $b->disponible)
        );

        return $precios === [] ? null : min($precios);
    }
}
