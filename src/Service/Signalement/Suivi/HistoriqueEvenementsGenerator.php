<?php

namespace App\Service\Signalement\Suivi;

use App\Entity\Arrete;
use App\Entity\Enum\SignalementStatus;
use App\Entity\Enum\SuiviCategory;
use App\Entity\Signalement;
use App\Manager\SuiviManager;
use App\Repository\ArreteRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Utils\Address\AddressParser;
use App\Utils\Address\CommuneHelper;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class HistoriqueEvenementsGenerator
{
    private const string HISTO_EVENTS_DATE_FORMAT = 'd/m/Y';

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly ArreteRepository $arreteRepository,
        private readonly SuiviManager $suiviManager,
        private readonly UserRepository $userRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire(env: 'FEATURE_HISTO_ADDRESS')]
        private readonly bool $featureHistoAddress,
    ) {
    }

    public function generate(Signalement $signalement): void
    {
        if (!$this->featureHistoAddress) {
            return;
        }

        $signalementsSameAddress = $this->signalementRepository->findOnSameAddress(
            signalement: $signalement,
            exclusiveStatus: [],
            excludedStatus: SignalementStatus::excludedStatuses(false)
        );

        $arretesSameAddress = $this->findArretesSameAddress($signalement);

        if (\count($signalementsSameAddress) > 0 || \count($arretesSameAddress) > 0) {
            $description = 'Voici l\'historique des évènements qui ont été enregistrés sur Signal Logement à cette adresse : <br/>';
            foreach ($signalementsSameAddress as $signalementSameAddress) {
                $description .= sprintf(
                    '- Le dossier %s de %s, enregistré le %s (statut du dossier : %s)<br/>',
                    $signalementSameAddress->getReference(),
                    $signalementSameAddress->getNomOccupant(),
                    $signalementSameAddress->getCreatedAt()->format(self::HISTO_EVENTS_DATE_FORMAT),
                    SignalementStatus::getLabelList()[$signalementSameAddress->getStatut()->name]
                );
            }
            foreach ($arretesSameAddress as $arrete) {
                if ($arrete->getDateMainLevee()) {
                    $description .= sprintf(
                        '- Un arrêté de type %s, pris le %s avec main levée le %s<br/>',
                        $arrete->getTypeArrete()->value,
                        $arrete->getDateArrete()->format(self::HISTO_EVENTS_DATE_FORMAT),
                        $arrete->getDateMainLevee()->format(self::HISTO_EVENTS_DATE_FORMAT)
                    );
                } else {
                    $description .= sprintf(
                        '- Un arrêté de type %s, pris le %s sans main levée renseignée</br>',
                        $arrete->getTypeArrete()->value,
                        $arrete->getDateArrete()->format(self::HISTO_EVENTS_DATE_FORMAT)
                    );
                }
            }
            // TODO: Remplacer par le bon lien et filtres une fois le ticket #5949 traitée.
            // https://github.com/MTES-MCT/histologe/issues/5949
            $description .= sprintf(
                '<br/>Pour plus d\'informations, consultez l\'<a href="%s">historique des événements</a>.',
                $this->urlGenerator->generate('back_signalement_same_address_index')
            );
            $this->suiviManager->createSuivi(
                signalement: $signalement,
                description: $description,
                category: SuiviCategory::SIGNALEMENT_HISTORIQUE_EVENEMENT,
                user: $this->userRepository->findOneBy(['email' => $this->parameterBag->get('user_system_email')])
            );
        }
    }

    /**
     * @return Arrete[]
     */
    private function findArretesSameAddress(Signalement $signalement): array
    {
        $banId = $signalement->getBanIdOccupant();
        if (null !== $banId && '' !== $banId && '0' !== $banId) {
            $arretes = $this->arreteRepository->findByBanId($banId);

            if ([] !== $arretes) {
                return $arretes;
            }
        }

        $address = AddressParser::parse($signalement->getAdresseOccupant());

        $houseNumber = $address['number'];
        if (null !== $address['suffix'] && null !== $address['number']) {
            $suffix = strtolower($address['suffix']);
            $houseNumber = [
                $address['number'].$suffix,
                $address['number'].' '.$suffix,
            ];
        }

        $city = CommuneHelper::getCommuneFromArrondissement($signalement->getVilleOccupant());

        return $this->arreteRepository->findByAddress(
            housenumber: $houseNumber,
            street: $address['street'],
            postCode: $signalement->getCpOccupant(),
            city: $city
        );
    }
}
