<?php

namespace App\Controller\Back;

use App\Entity\Signalement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/signalements')]
class BackSignalementQualificationController extends AbstractController
{
    #[Route('/{uuid}/qualification/{label}/editer', name: 'back_signalement_qualification_editer')]
    public function editQualification(
        Signalement $signalement,
        EntityManagerInterface $entityManager
    ) {
    }
}
