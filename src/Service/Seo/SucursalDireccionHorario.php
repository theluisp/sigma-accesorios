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

    /** Traducción de día en inglés (como los espera schema.org) a español. */
    private const DIAS_ES = [
        'Monday' => 'lunes',
        'Tuesday' => 'martes',
        'Wednesday' => 'miércoles',
        'Thursday' => 'jueves',
        'Friday' => 'viernes',
        'Saturday' => 'sábado',
        'Sunday' => 'domingo',
    ];

    /**
     * Horarios en texto legible para mostrar en la tarjeta de sucursales
     * (pedido explícito del usuario, sep 2026: "agregale esa info de
     * horarios y direccion... recuerda solo cp y horarios no todo por
     * tema de seguridad" — por eso esta tarjeta muestra horarios + CP,
     * pero nunca colonia/calle, aunque SÍ estén disponibles arriba para el
     * JSON-LD). Reusa la misma DATOS que NegocioJsonLd en vez de duplicar
     * los horarios en otro lugar.
     *
     * @return string[] ej. ["Lunes a viernes: 10:00 a.m. – 5:00 p.m.", "Martes: 6:00 p.m. – 10:00 p.m."]
     */
    public function horariosLegibles(string $claveSucursal): array
    {
        $datos = self::DATOS[$claveSucursal] ?? null;
        if ($datos === null) {
            return [];
        }

        return array_map(
            fn (array $horario): string => $this->formatearDias($horario[0]).': '.$this->formatearHora($horario[1]).' – '.$this->formatearHora($horario[2]),
            $datos['horarios'],
        );
    }

    /** @param string[] $dias */
    private function formatearDias(array $dias): string
    {
        // Caso más común: los 5 días hábiles completos.
        if ($dias === ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday']) {
            return 'Lunes a viernes';
        }

        $nombres = array_map(
            fn (string $dia): string => self::DIAS_ES[$dia] ?? $dia,
            $dias,
        );

        if (\count($nombres) === 1) {
            return ucfirst($nombres[0]);
        }

        $ultimo = array_pop($nombres);

        return ucfirst(implode(', ', $nombres).' y '.$ultimo);
    }

    /** "17:00" → "5:00 p.m." (formato de 12 horas, más natural para un cliente que 24h). */
    private function formatearHora(string $hhmm): string
    {
        [$horas, $minutos] = array_map('intval', explode(':', $hhmm));
        $sufijo = $horas >= 12 ? 'p.m.' : 'a.m.';
        $horas12 = $horas % 12;
        if ($horas12 === 0) {
            $horas12 = 12;
        }

        return sprintf('%d:%02d %s', $horas12, $minutos, $sufijo);
    }
}
