<?php

namespace App\Controller\Back;

use App\Repository\DuplicateAddresseDetectionRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bo/duplication-adresse')]
class DuplicateAddressDetectionController extends AbstractController
{
    #[Route('/', name: 'back_duplicate_address_detection_index')]
    public function index(
        DuplicateAddresseDetectionRepository $duplicateAddresseDetectionRepository,
    ): Response {
        $duplicateAddresses = $duplicateAddresseDetectionRepository->findBy([], ['id' => 'DESC']);

        return $this->render('back/duplicate_address_detection/index.html.twig', [
            'duplicateAddresses' => $duplicateAddresses,
        ]);
    }
}
