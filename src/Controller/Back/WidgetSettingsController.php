<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Repository\CommuneRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Security\Voter\UserVoter;
use App\Service\DashboardWidget\WidgetSettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/bo')]
class WidgetSettingsController extends AbstractController
{
    #[Route('/widget-settings', name: 'back_widget_settings')]
    public function index(
        TerritoryRepository $territoryRepository,
        CommuneRepository $communeRepository,
        PartnerRepository $partnerRepository,
        TagRepository $tagRepository,
        SignalementRepository $signalementRepository,
        SerializerInterface $serializer,
        Security $security,
        #[MapQueryParameter] ?int $territoryId = null,
    ): Response {
        $territories = $territoryRepository->findBy(['isActive' => 1]);
        $partners = $tags = $epcis = $communesAndZipCodes = [];
        /** @var User $user */
        $user = $this->getUser();
        $canSeeNDE = $security->isGranted(UserVoter::SEE_NDE, $user);

        $territory = ($security->isGranted('ROLE_ADMIN') && null !== $territoryId)
            ? $territoryRepository->find($territoryId)
            : $user->getTerritory();

        if ($territory instanceof Territory) {
            $epcis = $communeRepository->findEpciByCommuneTerritory($territory);
            $partners = $partnerRepository->findBy(['territory' => $territory, 'isArchive' => false]);
            $tags = $tagRepository->findBy(['territory' => $territory, 'isArchive' => false]);
            $communes = $signalementRepository->findCities(territory: $territory);
            $zipCodes = $signalementRepository->findZipcodes(territory: $territory);
            $suggestionsCommuneZipCode = [...$communes, ...$zipCodes];
            $communesAndZipCodes = array_map(
                fn ($suggestion): string => $suggestion['city'] ?? $suggestion['zipcode'],
                $suggestionsCommuneZipCode
            );
        }

        $widgetSettings = $serializer->serialize(
            new WidgetSettings($user, $territories, $canSeeNDE, $partners, $communesAndZipCodes, $epcis, $tags),
            'json'
        );

        return new Response(
            $widgetSettings,
            Response::HTTP_OK,
            ['content-type' => 'application/json']
        );
    }
}
