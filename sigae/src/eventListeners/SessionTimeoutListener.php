<?php

namespace Sigae\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class SessionTimeoutListener {
    private RouterInterface $router;
    private const INACTIVIDAD_MAX_SESION = 600; // límite de 10 minutos de inactividad

    public function __construct(RouterInterface $router){
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void{
        if ($event->isMainRequest()) {
            $this->verificarSesion($event);
        }
    }

    private function verificarSesion(RequestEvent $event): void{
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Verifica si la sesión tiene el tiempo de última solicitud
        if (!isset($_SESSION["ultima_solicitud"])) {
            $this->logout($event);
            return;
        }

        // Calcula el tiempo de inactividad
        $inactividad = time() - $_SESSION["ultima_solicitud"];
        if ($inactividad > self::INACTIVIDAD_MAX_SESION) {
            $this->logout($event);
            return;
        }

        // Actualiza la última solicitud y regenera el ID de sesión
        $_SESSION["ultima_solicitud"] = time();
        session_regenerate_id(true);
    }

    private function logout(RequestEvent $event): void{
        // Redirige a la ruta del logout
        $logoutUrl = $this->router->generate('logout');
        $event->setResponse(new RedirectResponse($logoutUrl));
    }
}
