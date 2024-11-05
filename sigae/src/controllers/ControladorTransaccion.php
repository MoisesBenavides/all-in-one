<?php

namespace Sigae\Controllers;
use Sigae\Models\Transaccion;
use Sigae\Models\Producto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use PDOException;
use Exception;

class ControladorTransaccion extends AbstractController{
    private $transaccion;

    function createTransaction(): Response|RedirectResponse{
        $response=['success' => false, 'errors' => []];

        $rol=$_SESSION['rol'];
        
        switch($rol){
            case 'gerente':
                
            default:
                return $this->render('errors/errorAcceso.html.twig');
        }


    }
}