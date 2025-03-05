<?php

namespace App\Controller;

use App\Entity\Bailleur;
use App\Repository\BailleurRepository;
use App\Service\Signalement\ZipcodeProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class BailleurController extends AbstractController
{
    #[Route('/bailleurs', name: 'app_bailleur')]
    public function index(
        BailleurRepository $bailleurRepository,
        ZipcodeProvider $zipcodeProvider,
        #[MapQueryParameter] string $name,
        #[MapQueryParameter] string $inseecode,
        #[MapQueryParameter] ?bool $sanitize,
    ): JsonResponse {
        $name = trim($name);
        $territory = $zipcodeProvider->getTerritoryByInseeCode($inseecode);
        $bailleurs = !empty($name) ? $bailleurRepository->findBailleursBy($name, $territory) : [];

        if ($sanitize) {
            $bailleurCollection = $this->sanitizeBailleurs($bailleurs, $name);

            return $this->json(array_values($bailleurCollection->toArray()));
        }

        return $this->json($bailleurs);
    }

    private function sanitizeBailleurs(array $bailleurs, string $name): ArrayCollection
    {
        return (new ArrayCollection($bailleurs))
            /* @var Bailleur $bailleurItem */
            ->filter(function ($bailleurItem) use ($name) {
                if (str_starts_with($bailleurItem->getName(), Bailleur::BAILLEUR_RADIE)) {
                    $name = strtolower($name);
                    $name = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);

                    return str_contains(strtolower($this->sanitizeName($bailleurItem->getName())), $name) || str_contains(strtolower($bailleurItem->getRaisonSociale()), $name);
                }

                return true;
            })
            ->map(fn ($bailleurItem) => $bailleurItem->setName($this->sanitizeName($bailleurItem->getName())));
    }

    private function sanitizeName($name): string
    {
        return trim(str_replace(Bailleur::BAILLEUR_RADIE, '', $name));
    }
}
