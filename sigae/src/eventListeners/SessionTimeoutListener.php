<?php

namespace Sigae\eventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\RouterInterface;

class SessionTimeoutListener {
    private RouterInterface $router;
    private const INACTIVIDAD_MAX_SESION = 600; // límite de 10 minutos de inactividad
    
    // Rutas que no deben verificar una sesión ya activa
    private array $rutasExcluidas = [
        'showLandingPage', 
        'login', 
        'doLogin', 
        'signup', 
        'doSignup', 
        'doSignupOAuth', 
        'forgotPassword', 
        'loginAioEmployee', 
        'doLoginAioEmployee',
        'logout'
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
        if ($inactividad > self::INACTIVIDAD_MAX_SESION) {
            $this->logout($event);
            return;
        }

        // Actualiza la última solicitud y regenera el ID de sesion
        $_SESSION["ultima_solicitud"] = time();
        session_regenerate_id(true);
    }

    private function logout(RequestEvent $event): void{
        // Redirige a la ruta del logout
        $logoutUrl = $this->router->generate('logout');
        $event->setResponse(new RedirectResponse($logoutUrl));
    }
}
