<?php

namespace App\Service\Seo;

/**
 * Colonia, código postal y horario de apertura de cada sucursal — datos
 * reales confirmados por el usuario (sep 2026, ver docs/seo-notas.md),
 * usados para el LocalBusiness JSON-LD (ver NegocioJsonLd). Hardcodeado
 * aquí en vez de columnas nuevas en App\Entity\Sucursal a propósito: esta
 * info casi no cambia (a diferencia del inventario, que se sincroniza
 * varias veces al día desde el Sheet), así que no vale la pena una
 * migración ni un panel de admin para editarla — mismo criterio que
 * MarcaCatalog para datos de configuración estables.
 *
 * NO se tiene calle/número exacto de ninguna sucursal, solo colonia + CP +
 * ciudad — por eso el JSON-LD no incluye streetAddress (ver NegocioJsonLd):
 * mejor omitirlo que inventar un valor que Google podría tomar como dato
 * real e indexar mal la ubicación.
 */
final class SucursalDireccionHorario
{
    private const DATOS = [
        'real_de_guadalupe' => [
            'colonia' => 'Real de Guadalupe',
            'codigoPostal' => '72016',
            // Cada entrada: [días (en inglés, como las espera schema.org
            // OpeningHoursSpecification), hora apertura, hora cierre].
            // Martes tiene su propio segundo turno (6pm-10pm) en vez del
            // de lunes/miércoles/jueves/viernes (7pm-9pm) — confirmado con
            // el usuario, ver docs/seo-notas.md.
            'horarios' => [
                [['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], '10:00', '17:00'],
                [['Monday', 'Wednesday', 'Thursday', 'Friday'], '19:00', '21:00'],
                [['Tuesday'], '18:00', '22:00'],
                [['Saturday'], '10:00', '14:00'],
            ],
        ],
        'capu' => [
            'colonia' => 'Capu',
            'codigoPostal' => '72050',
            'horarios' => [
                [['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], '09:00', '16:00'],
            ],
        ],
    ];

    /**
     * @return array{colonia: string, codigoPostal: string, horarios: array<int, array{0: string[], 1: string, 2: string}>}|null
     */
    public function paraSucursal(string $claveSucursal): ?array
    {
        return self::DATOS[$claveSucursal] ?? null;
    }
}
