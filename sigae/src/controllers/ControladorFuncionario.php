<?php

namespace Sigae\Controllers;
use Sigae\Models\Funcionario;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class ControladorFuncionario extends AbstractController {
    private $funcionario;
}