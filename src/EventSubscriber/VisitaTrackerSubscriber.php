<?php

namespace App\EventSubscriber;

use App\Repository\VisitaDiariaRepository;
use App\Repository\VisitaPaginaRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Contador básico de visitas diarias, sitewide, MÁS un contador de vistas
 * por página. A propósito NO es un sistema de analítica completo (no
 * guarda IP, user-agent, ni sesión de nadie) — solo cuenta "cuántas veces"
 * y "en qué página" (App\Entity\VisitaDiaria y App\Entity\VisitaPagina).
 *
 * Deduplicación "un visitante = una visita contada por día" (VisitaDiaria)
 * vía una cookie propia (sigma_visita) con la fecha del último día ya
 * contado — sin consentimiento de cookies porque no es tracking de
 * terceros ni guarda nada identificable de la persona, solo una fecha.
 * VisitaPagina NO usa ese candado (ver su docblock): sería inútil para
 * saber qué página se visita más si solo se pudiera contar la primera
 * página que abre cada quien en el día.
 *
 * Se ignoran: rutas /admin (para no inflar el contador con las propias
 * visitas del dueño al panel), peticiones que no sean GET, y respuestas de
 * error (404/500) — solo cuenta cargas de página reales y exitosas.
 *
 * Se ignoran también los bots/crawlers (ver esBot()) — pedido explícito
 * del usuario, sep 2026: "si publico el link en un grupo automáticamente
 * sube la cantidad de visitas... con que vean el link lo cuentas como
 * visita". Eso pasa porque WhatsApp, Facebook, Telegram, etc. mandan su
 * propio servidor a visitar la URL para armar la tarjeta de vista previa
 * (usa el Open Graph que ya tiene el sitio) ANTES de que ningún humano le
 * dé clic — esa petición es un GET real y exitoso, así que sin este
 * filtro se contaba como visita. Los buscadores (Googlebot, Bingbot, etc.)
 * también se excluyen del conteo por el mismo motivo (no son personas),
 * aunque SÍ se les sigue dejando entrar y rastrear el sitio con
 * normalidad — este filtro es solo para el contador, no bloquea nada.
 */
final class VisitaTrackerSubscriber implements EventSubscriberInterface
{
    private const COOKIE_NAME = 'sigma_visita';

    /**
     * Substrings (siempre en minúsculas) que se buscan dentro del
     * User-Agent para identificar bots/crawlers. No es exhaustivo —
     * cubre los casos reales que motivaron esto (previsualizadores de
     * links de apps de mensajería/redes) más los buscadores y crawlers
     * SEO más comunes, más genéricos de respaldo ("bot", "crawl",
     * "spider") y herramientas de línea de comandos/scripts.
     *
     * @var string[]
     */
    private const PATRONES_BOT = [
        // Previsualización de links (la causa principal reportada por el
        // usuario: compartir el link en un grupo infla las visitas).
        'whatsapp', 'facebookexternalhit', 'telegrambot', 'discordbot',
        'slackbot', 'twitterbot', 'linkedinbot', 'pinterest', 'skypeuripreview',
        'redditbot', 'embedly', 'outbrain', 'quora link preview', 'vkshare',
        'w3c_validator', 'whatsapp-preview',
        // Buscadores y crawlers SEO/monitoreo — se dejan entrar (robots.txt
        // no los bloquea), solo no cuentan como visita humana.
        'googlebot', 'bingbot', 'bingpreview', 'yandex', 'baiduspider',
        'duckduckbot', 'applebot', 'ia_archiver', 'semrushbot', 'ahrefsbot',
        'mj12bot', 'dotbot', 'petalbot', 'bytespider', 'seznambot',
        // Genéricos de respaldo + scripts/herramientas de línea de comandos.
        'bot', 'crawl', 'spider', 'curl/', 'wget/', 'python-requests',
        'go-http-client', 'okhttp', 'headlesschrome', 'phantomjs',
    ];

    public function __construct(
        private readonly VisitaDiariaRepository $visitas,
        private readonly VisitaPaginaRepository $visitasPorPagina,
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

        if ($this->esBot($request->headers->get('User-Agent', ''))) {
            return;
        }

        // Vistas por página: sin candado de cookie a propósito (ver
        // docblock de la clase y de VisitaPagina).
        $this->visitasPorPagina->registrarVisita($request->getPathInfo());

        $hoy = new \DateTimeImmutable('today');
        $hoyTexto = $hoy->format('Y-m-d');

        if ($request->cookies->get(self::COOKIE_NAME) === $hoyTexto) {
            // Ya contamos a este navegador hoy, no sumar de nuevo al
            // contador SITEWIDE (VisitaPagina de arriba ya se contó
            // aparte, eso no lleva este candado).
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

    private function esBot(string $userAgent): bool
    {
        if ($userAgent === '') {
            // Un navegador real siempre manda User-Agent; su ausencia es
            // señal de un script/bot que no se molestó en poner uno.
            return true;
        }

        $userAgentMinusculas = strtolower($userAgent);
        foreach (self::PATRONES_BOT as $patron) {
            if (str_contains($userAgentMinusculas, $patron)) {
                return true;
            }
        }

        return false;
    }
}
