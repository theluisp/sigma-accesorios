<?php

namespace App\EventSubscriber;

use App\Repository\VisitaDiariaRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Contador básico de visitas diarias, sitewide. A propósito NO es un
 * sistema de analítica completo (no guarda IP, user-agent, ni páginas
 * visitadas) — solo suma 1 al contador del día actual (App\Entity\VisitaDiaria)
 * la primera vez que un navegador visita el sitio ese día.
 *
 * Deduplicación "un visitante = una visita contada por día" vía una cookie
 * propia (sigma_visita) con la fecha del último día ya contado — sin
 * consentimiento de cookies porque no es tracking de terceros ni guarda
 * nada identificable de la persona, solo una fecha.
 *
 * Se ignoran: rutas /admin (para no inflar el contador con las propias
 * visitas del dueño al panel), peticiones que no sean GET, y respuestas de
 * error (404/500) — solo cuenta cargas de página reales y exitosas.
 */
final class VisitaTrackerSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'sigma_visita';

    public function __construct(
        private readonly VisitaDiariaRepository $visitas,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        if ($request->getMethod() !== 'GET') {
            return;
        }

        if (str_starts_with($request->getPathInfo(), '/admin')) {
            return;
        }

        $response = $event->getResponse();
        if ($response->getStatusCode() >= 400) {
            return;
        }

        $hoy = new \DateTimeImmutable('today');
        $hoyTexto = $hoy->format('Y-m-d');

        if ($request->cookies->get(self::COOKIE_NAME) === $hoyTexto) {
            // Ya contamos a este navegador hoy, no sumar de nuevo.
            return;
        }

        $this->visitas->registrarVisita($hoy);

        $response->headers->setCookie(
            Cookie::create(self::COOKIE_NAME, $hoyTexto)
                ->withExpires((new \DateTimeImmutable('tomorrow'))->getTimestamp())
                ->withHttpOnly(true)
                ->withSameSite(Cookie::SAMESITE_LAX)
        );
    }
}
