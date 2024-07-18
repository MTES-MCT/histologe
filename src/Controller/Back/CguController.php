<?php

namespace App\Controller\Back;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/cgu')]
class CguController extends AbstractController
{
    #[Route('/valider', name: 'cgu_bo_confirm', methods: 'POST')]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        ParameterBagInterface $parameterBag,
    ): Response {
        $decodedRequest = json_decode($request->getContent());
        if ($this->isCsrfTokenValid('cgu_bo_confirm', $decodedRequest->_token)) {
            $currentCguVersion = $parameterBag->get('cgu_current_version');
            /** @var User $user */
            $user = $this->getUser();
            $user->setCguVersionChecked($currentCguVersion);
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->json(['response' => 'success']);
        }

        return $this->json(['response' => 'error'], 400);
    }
}
