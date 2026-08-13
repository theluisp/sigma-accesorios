<?php

namespace App\Dto;

/**
 * Disponibilidad de un producto en una sucursal concreta (Real De Guadalupe / Capu).
 */
final class BranchStock
{
    public function __construct(
        public readonly string $sucursalKey,
        public readonly string $sucursalLabel,
        public readonly int $stock,
        public readonly float $precio,
        public readonly int $descuentoPorcentaje,
        public readonly bool $disponible,
    ) {
    }
}
