<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/')]
class ErrorController extends AbstractController
{
    #[Route('/error-502', name: 'error_502')]
    public function badGateway(): Response
    {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_BAD_GATEWAY);

        return $response;
    }

    #[Route('/error-504', name: 'error_504')]
    public function gatewayTimeout(): Response
    {
        $response = new Response();
        $response->setStatusCode(Response::HTTP_GATEWAY_TIMEOUT);

        return $response;
    }
}
