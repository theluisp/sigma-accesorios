<?php

namespace App\Service\Catalog;

use App\Dto\BranchStock;

/**
 * Convierte una fila cruda del Sheet (export de Rappi) en un BranchStock + los
 * datos de producto (nombre/descripción), limpiando el formato que trae Rappi.
 *
 * Columnas esperadas (A..L): id producto, id tienda, sucursal, sku, (vacía),
 * slug interno, producto, descripcion, stock ("3Und"), precio, descuento %, disponible (SI/NO).
 */
final class CatalogRowMapper
{
    private const COL_SLUG = 5;
    private const COL_NOMBRE = 6;
    private const COL_DESCRIPCION = 7;
    private const COL_STOCK = 8;
    private const COL_PRECIO = 9;
    private const COL_DESCUENTO = 10;
    private const COL_DISPONIBLE = 11;

    /**
     * @param array<int, string> $row
     *
     * @return array{slug: string, nombre: string, descripcion: string, branch: BranchStock}|null
     *         null si la fila viene vacía o sin slug (no se puede identificar el producto)
     */
    public function map(array $row, string $sucursalKey, string $sucursalLabel): ?array
    {
        $cell = static fn (int $i): string => trim((string) ($row[$i] ?? ''));

        $slug = $cell(self::COL_SLUG);
        if ($slug === '') {
            return null;
        }

        return [
            'slug' => $slug,
            'nombre' => $this->fixEncoding($cell(self::COL_NOMBRE)),
            'descripcion' => $this->fixEncoding($cell(self::COL_DESCRIPCION)),
            'branch' => new BranchStock(
                sucursalKey: $sucursalKey,
                sucursalLabel: $sucursalLabel,
                stock: $this->parseStock($cell(self::COL_STOCK)),
                precio: $this->parseNumber($cell(self::COL_PRECIO)),
                descuentoPorcentaje: (int) $this->parseNumber($cell(self::COL_DESCUENTO)),
                disponible: strtoupper($cell(self::COL_DISPONIBLE)) === 'SI',
            ),
        ];
    }

    /**
     * "3Und" -> 3, "0Und" -> 0, "" -> 0
     */
    private function parseStock(string $raw): int
    {
        if (preg_match('/-?\d+/', $raw, $matches) === 1) {
            return (int) $matches[0];
        }

        return 0;
    }

    private function parseNumber(string $raw): float
    {
        $normalized = str_replace(',', '.', $raw);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    /**
     * Intenta reparar texto que quedó doblemente codificado al exportar desde Rappi
     * (aparece como "Ã©" en vez de "é", o emojis rotos como "ð§©"). Si el texto ya
     * está bien, lo deja tal cual.
     */
    private function fixEncoding(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        // Si al re-interpretar como Latin1 -> UTF-8 el resultado sigue siendo UTF-8 válido
        // y no contiene el caracter de reemplazo, es que estaba doblemente codificado.
        $repaired = @mb_convert_encoding($text, 'UTF-8', 'ISO-8859-1');

        if ($repaired !== false && mb_check_encoding($repaired, 'UTF-8') && !str_contains($repaired, "\u{FFFD}")) {
            // Heurística: si el "arreglo" quitó caracteres típicos de doble-codificación (Ã, Â, ð)
            // nos quedamos con la versión reparada; si no, la original ya estaba bien.
            if (preg_match('/[ÃÂð]/u', $text) === 1) {
                return $repaired;
            }
        }

        return $text;
    }
}
