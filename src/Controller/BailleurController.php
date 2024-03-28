<?php

namespace App\Controller;

use App\Entity\Bailleur;
use App\Repository\BailleurRepository;
use App\Service\Signalement\ZipcodeProvider;
use Doctrine\Common\Collections\ArrayCollection;
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
        #[MapQueryParameter] ?bool $sanitize,
    ): JsonResponse {
        $name = trim($name);
        $zip = ZipcodeProvider::getZipCode($postcode);
        $bailleurs = !empty($name) ? $bailleurRepository->findBailleursBy($name, $zip) : [];

        if ($sanitize) {
            $bailleurCollection = $this->sanitizeBailleurs($bailleurs, $name);

            return $this->json(array_values($bailleurCollection->toArray()));
        }

        return $this->json($bailleurs);
    }

    private function sanitizeBailleurs(array $bailleurs, string $name): ArrayCollection
    {
        return (new ArrayCollection($bailleurs))
            ->filter(fn ($bailleurItem) => str_contains(
                strtolower($this->sanitizeName($bailleurItem->getName())),
                strtolower($name)))
            ->map(fn ($bailleurItem) => $bailleurItem->setName($this->sanitizeName($bailleurItem->getName())));
    }

    private function sanitizeName($name): string
    {
        return trim(str_replace(Bailleur::BAILLEUR_RADIE, '', $name));
    }
}
