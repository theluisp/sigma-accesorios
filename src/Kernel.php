<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        // El hosting (Hostinger) no tiene configurada su propia zona
        // horaria y corre en UTC por default — sin esto, TODAS las
        // fechas/horas de la app (visitas, pedidos, productos) salían 6
        // horas adelantadas respecto a la hora real de Puebla/CDMX. Se
        // fija aquí (no en php.ini, que no podemos tocar en shared
        // hosting) porque el constructor del Kernel corre tanto para
        // peticiones web (public/index.php) como para bin/console, así
        // que es un solo lugar que cubre toda la aplicación.
        date_default_timezone_set('America/Mexico_City');

        parent::__construct($environment, $debug);
    }
}
