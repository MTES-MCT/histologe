<?php

namespace App\Controller\Api;

use App\Dto\Api\Request\SignalementRequest;
use App\Dto\Api\Response\SignalementResponse;
use App\Entity\Partner;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\Api\SignalementResponseFactory;
use App\Manager\SignalementManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Service\Security\PartnerAuthorizedResolver;
use App\Service\Signalement\Qualification\SignalementQualificationUpdater;
use App\Service\Signalement\ReferenceGenerator;
use App\Service\Signalement\SignalementApiFactory;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
class SignalementCreateController extends AbstractController
{
    public function __construct(
        private readonly SignalementResponseFactory $signalementResponseFactory,
        private readonly PartnerAuthorizedResolver $partnerAuthorizedResolver,
        private readonly ValidatorInterface $validator,
        private readonly SignalementApiFactory $signalementApiFactory,
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementManager $signalementManager,
        private readonly SignalementQualificationUpdater $signalementQualificationUpdater,
        private readonly UserManager $userManager,
        private readonly ReferenceGenerator $referenceGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @throws \DateMalformedStringException
     */
    #[OA\Post(
        path: '/api/signalements',
        description: 'Création d\'un signalement',
        summary: 'Création d\'un signalement',
        security: [['Bearer' => []]],
        requestBody: new OA\RequestBody(
            description: 'Payload d\'un signalement',
            content: new OA\JsonContent(
                examples: [
                    new OA\Examples(
                        example: 'Création d\'un signalement',
                        summary: 'Création d\'un signalement',
                        description: 'Exemple de payload de création d\'un signalement.',
                        value: [
                            'partenaireUuid' => '85401893-8d92-11f0-8aa8-f6901f1203f4',
                            'adresseOccupant' => '151 chemin de la route',
                            'codePostalOccupant' => '34090',
                            'communeOccupant' => 'Montpellier',
                            'etageOccupant' => '2',
                            'escalierOccupant' => 'B',
                            'numAppartOccupant' => '24B',
                            'adresseAutreOccupant' => 'Résidence les oliviers',
                            'profilDeclarant' => 'TIERS_PARTICULIER',
                            'lienDeclarantOccupant' => 'PROCHE',
                            'isLogementSocial' => false,
                            'isLogementVacant' => false,
                            'nbOccupantsLogement' => 4,
                            'nbEnfantsDansLogement' => 2,
                            'isEnfantsMoinsSixAnsDansLogement' => true,
                            'natureLogement' => 'appartement',
                            'natureLogementAutre' => null,
                            'etageAppartement' => 'RDC',
                            'appartementAvecFenetres' => true,
                            'nombreEtages' => 0,
                            'anneeConstruction' => 1970,
                            'nombrePieces' => 4,
                            'superficie' => 85.5,
                            'isPieceAVivre9m' => true,
                            'isCuisine' => true,
                            'isCuisineCollective' => null,
                            'isSalleDeBain' => false,
                            'isSalleDeBainCollective' => true,
                            'isWc' => false,
                            'isWcCollectif' => false,
                            'isWcCuisineMemePiece' => false,
                            'typeChauffage' => 'ELECTRIQUE',
                            'isBail' => true,
                            'isDpe' => true,
                            'anneeDpe' => '2021',
                            'classeEnergetique' => 'D',
                            'isEtatDesLieux' => true,
                            'dateEntreeLogement' => '2018-06-01',
                            'montantLoyer' => 765.50,
                            'isPaiementLoyersAJour' => true,
                            'isAllocataire' => true,
                            'caisseAllocations' => 'CAF',
                            'dateNaissanceAllocataire' => '2001-03-15',
                            'numAllocataire' => '1234567890',
                            'typeAllocation' => 'APL',
                            'montantAllocation' => 250.75,
                            'isAccompagnementTravailleurSocial' => true,
                            'accompagnementTravailleurSocialNomStructure' => 'CCAS de Montpellier',
                            'isBeneficiaireRsa' => false,
                            'isBeneficiaireFsl' => false,
                            'isBailleurAverti' => true,
                            'dateBailleurAverti' => '2025-02-01',
                            'moyenInformationBailleur' => 'courrier',
                            'reponseBailleur' => 'Le bailleur n\'a pas donné suite.',
                            'isDemandeRelogement' => false,
                            'isSouhaiteQuitterLogement' => false,
                            'isPreavisDepartDepose' => false,
                            'isLogementAssure' => true,
                            'isAssuranceContactee' => true,
                            'reponseAssurance' => 'L\'assurance refuse de couvrir les dégâts.',
                            'civiliteOccupant' => 'Mme',
                            'nomOccupant' => 'Dupont',
                            'prenomOccupant' => 'Marie',
                            'mailOccupant' => 'marie.dupont@example.com',
                            'telOccupant' => '0639987654',
                            'typeBailleur' => 'PARTICULIER',
                            'denominationBailleur' => null,
                            'nomBailleur' => 'Vignon',
                            'prenomBailleur' => 'René',
                            'mailBailleur' => 'rene.vignon@example.com',
                            'telBailleur' => '0639980851',
                            'adresseBailleur' => '12 avenue des bartas',
                            'codePostalBailleur' => '34000',
                            'communeBailleur' => 'Montpellier',
                            'structureDeclarant' => null,
                            'nomDeclarant' => 'El Allali',
                            'prenomDeclarant' => 'Hakim',
                            'mailDeclarant' => 'el-allali.hakim@example.com',
                            'telDeclarant' => '0639980906',
                            'denominationAgence' => 'IMMO 3600',
                            'nomAgence' => 'Apollo-Sanchez',
                            'prenomAgence' => 'Victoria',
                            'mailAgence' => 'victoria.apollo@immo3600.com',
                            'telAgence' => '0639988821',
                            'desordres' => [
                                [
                                    'identifiant' => 'desordres_logement_humidite_salle_de_bain',
                                    'precisions' => [
                                        'desordres_logement_humidite_salle_de_bain_details_machine_non',
                                        'desordres_logement_humidite_salle_de_bain_details_moisissure_apres_nettoyage_oui',
                                        'desordres_logement_humidite_salle_de_bain_details_fuite_non',
                                    ],
                                    'precisionLibres' => [],
                                ],
                                [
                                    'identifiant' => 'desordres_batiment_nuisibles_autres',
                                    'precisions' => [],
                                    'precisionLibres' => [
                                        [
                                            'identifiant' => 'desordres_batiment_nuisibles_autres',
                                            'description' => 'Invasion de fourmis.',
                                        ],
                                    ],
                                ],
                            ],
                        ]
                    ),
                ],
                ref: '#/components/schemas/SignalementRequest',
            )
        ),
        tags: ['Signalements'],
    )]
    #[OA\Response(
        response: Response::HTTP_CREATED,
        description: 'Signalement crée avec succès',
        content: new OA\JsonContent(ref: new Model(type: SignalementResponse::class))
    )]
    #[OA\Response(
        response: Response::HTTP_BAD_REQUEST,
        description: 'Mauvaise payload (données invalides).',
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'message',
                    type: 'string',
                    example: 'Valeurs invalides pour les champs suivants :'
                ),
                new OA\Property(
                    property: 'status',
                    type: 'integer',
                    example: 400
                ),
                new OA\Property(
                    property: 'errors',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(
                                property: 'property',
                                type: 'string',
                                example: 'description'
                            ),
                            new OA\Property(
                                property: 'adresse',
                                type: 'string',
                                example: 'Veuillez renseigner une adresse'
                            ),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    )]
    #[Route('/signalements', name: 'api_signalements_create_post', methods: 'POST')]
    public function __invoke(
        #[MapRequestPayload(validationGroups: ['false'])]
        SignalementRequest $signalementRequest,
    ): JsonResponse {
        /** @var User $user */
        $user = $this->getUser();
        $partner = $this->partnerAuthorizedResolver->resolvePartner($user, $signalementRequest->partenaireUuid);
        $errors = $this->validator->validate($signalementRequest);
        if (count($errors) > 0) {
            throw new ValidationFailedException($signalementRequest, $errors);
        }
        $signalement = $this->signalementApiFactory->createFromSignalementRequest($signalementRequest);
        $this->signalementManager->updateDesordresAndScoreWithSuroccupationChanges($signalement, false);
        $this->signalementQualificationUpdater->updateQualificationFromScore($signalement);
        // domage de ne pas avoir toutes les erreurs dés le départ mais la vérif du territoire et des doublon demande des traitement intermédiaires
        $errors = $this->checkPartnerAndTerritory($partner, $signalementRequest, $signalement, $errors);
        $errors = $this->checkDuplicate($signalementRequest, $signalement, $errors);
        if (count($errors) > 0) {
            throw new ValidationFailedException($signalementRequest, $errors);
        }

        $this->entityManager->beginTransaction();
        $signalement->setReference($this->referenceGenerator->generate($signalement->getTerritory()));
        $this->signalementRepository->save($signalement, true);
        $this->entityManager->commit();
        $this->userManager->createUsagerFromSignalement($signalement, UserManager::OCCUPANT);
        $this->userManager->createUsagerFromSignalement($signalement, UserManager::DECLARANT);

        $resource = $this->signalementResponseFactory->createFromSignalement($signalement);

        return new JsonResponse($resource, Response::HTTP_CREATED);
    }

    private function checkPartnerAndTerritory(Partner $partner, SignalementRequest $signalementRequest, Signalement $signalement, ConstraintViolationListInterface $errors): ConstraintViolationListInterface
    {
        if ($partner->getTerritory() !== $signalement->getTerritory()) {
            $violation = new ConstraintViolation(
                'Vous n\'avez pas le droit de créer un signalement sur le territoire "'.$signalement->getTerritory()->getName().'".',
                null,
                [],
                $signalementRequest,
                null,
                $signalement->getAddressCompleteOccupant(false)
            );
            $errors->add($violation);
        }

        return $errors;
    }

    private function checkDuplicate(SignalementRequest $signalementRequest, Signalement $signalement, ConstraintViolationListInterface $errors): ConstraintViolationListInterface
    {
        $duplicates = $this->signalementRepository->findOnSameAddress(signalement: $signalement, compareNomOccupant: true);
        if (count($duplicates) > 0) {
            $violation = new ConstraintViolation(
                'Un signalement existe déjà à cette adresse ('.$signalement->getAddressCompleteOccupant(false).').',
                null,
                [],
                $signalementRequest,
                null,
                $signalement->getAddressCompleteOccupant(false)
            );
            $errors->add($violation);
        }

        return $errors;
    }
}
