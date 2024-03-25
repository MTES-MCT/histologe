<?php

namespace App\Controller;

use App\Repository\BailleurRepository;
use App\Service\Signalement\ZipcodeProvider;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;

class BailleurController extends AbstractController
{
    #[Route('/bailleurs', name: 'app_bailleur')]
    public function index(
        BailleurRepository $bailleurRepository,
        #[MapQueryParameter] string $name,
        #[MapQueryParameter] string $postcode,
    ): JsonResponse {
        $name = trim($name);
        $zip = ZipcodeProvider::getZipCode($postcode);
        $bailleurs = !empty($name) ? $bailleurRepository->findBailleursBy($name, $zip) : [];

        return $this->json($bailleurs);
    }
}
