<?php

namespace Sigae\EventListeners;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class SessionTimeoutListener {
    private RouterInterface $router;
    private const INACTIVIDAD_MAX_SESION = 1200; // límite de 20 minutos de inactividad
    
    // Rutas que no deben verificar una sesión ya activa
    private array $rutasExcluidas = [
        'showLandingPage', 
        'login', 
        'doLogin', 
        'signup', 
        'doSignup', 
        'doLoginOAuth', 
        'forgotPassword', 
        'loginAioEmployee', 
        'doLoginAioEmployee',
        'logout',
        'getClientEmail',
        'getClientProfilePhoto',
        'getClientSession',
        'getClientEmail',
        'getClientVehicles',
        'getServicesSchedule'
    ];

    public function __construct(RouterInterface $router){
        $this->router = $router;
    }

    public function onKernelRequest(RequestEvent $event): void{
        if ($event->isMainRequest()) {
            $this->verificarSesion($event);
        }
    }

    private function verificarSesion(RequestEvent $event): void{

        $request = $event->getRequest();
        $rutaActual = $request->attributes->get('_route');

        // Si la ruta actual es una ruta excluidas, no hace la verificación
        if (in_array($rutaActual, $this->rutasExcluidas, true)) {
            return;
        }

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
        
        // Si el tiempo de inactividad supera el límite, redirecciona al logout
        if ($inactividad > self::INACTIVIDAD_MAX_SESION) {
            $this->logout($event);
            return;
        }

        // Actualiza la última solicitud
        $_SESSION["ultima_solicitud"] = time();
    }

    private function logout(RequestEvent $event): void{
        // Redirige a la ruta del logout
        $logoutUrl = $this->router->generate('logout');
        $event->setResponse(new RedirectResponse($logoutUrl));
    }
}
