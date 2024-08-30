<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Manager\SignalementManager;
use App\Messenger\Message\ListExportMessage;
use App\Service\Signalement\Export\SignalementExportLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/export/signalement')]
class ExportSignalementController extends AbstractController
{
    public const SELECTABLE_COLS = [
        'TEL_SEC' => ['name' => 'Téléphone sec.', 'description' => 'Numéro de téléphone secondaire de l\'occupant du logement', 'export' => 'telephoneOccupantBis'],
        'INSEE' => ['name' => 'Code INSEE', 'description' => 'Le code INSEE de la commune du signalement', 'export' => 'inseeOccupant'],
        'ETAGE' => ['name' => 'Étage', 'description' => 'L\'étage du logement', 'export' => 'etageOccupant'],
        'ESCALIER' => ['name' => 'Escalier', 'description' => 'Le numéro d\'escalier du logement', 'export' => 'escalierOccupant'],
        'APPARTEMENT' => ['name' => 'Appartement', 'description' => 'Le numéro d\'appartement du logement', 'export' => 'numAppartOccupant'],
        'COMP_ADRESSE' => ['name' => 'Complément', 'description' => 'Le complément d\'adresse du logement', 'export' => 'adresseAutreOccupant'],
        'CRITICITE' => ['name' => 'Criticité au dépôt', 'description' => 'Score de criticité calculé automatiquement au dépôt du signalement', 'export' => 'score'],
        'ETIQUETTES' => ['name' => 'Étiquettes', 'description' => 'Les étiquettes ajoutées au signalement', 'export' => 'etiquettes'],
        'PHOTOS' => ['name' => 'Photos', 'description' => 'Le nom des fichiers photo ajoutés au signalement', 'export' => 'photos'],
        'DOCUMENTS' => ['name' => 'Documents', 'description' => 'Le nom des documents ajoutés au signalement', 'export' => 'documents'],
        'PROPRIETAIRE_AVERTI' => ['name' => 'Propriétaire averti', 'description' => 'Si le propriétaire a été averti ou non de la situation', 'export' => 'isProprioAverti'],
        'NB_PERSONNES' => ['name' => 'Nb personnes', 'description' => 'Le nombre de personnes occupant le logement', 'export' => 'nbPersonnes'],
        'MOINS_6_ANS' => ['name' => 'Enfants -6 ans', 'description' => 'Si oui ou non il y a des enfants de - de 6 ans dans le logement', 'export' => 'enfantsM6'],
        'NUM_ALLOCATAIRE' => ['name' => 'Numéro allocataire', 'description' => 'Le numéro d\'allocataire de l\'occupant', 'export' => 'numAllocataire'],
        'NATURE_LOGEMENT' => ['name' => 'Nature du logement', 'description' => 'La nature du logement (maison, appartement, autre)', 'export' => 'natureLogement'],
        'SUPERFICIE' => ['name' => 'Superficie', 'description' => 'La superficie du logement en m²', 'export' => 'superficie'],
        'NOM_BAILLEUR' => ['name' => 'Nom bailleur', 'description' => 'Le nom du bailleur du logement', 'export' => 'nomProprio'],
        'PREAVIS_DEPART' => ['name' => 'Préavis de départ', 'description' => 'Si le foyer a déposé un préavis de départ ou non', 'export' => 'isPreavisDepart'],
        'DEMANDE_RELOGEMENT' => ['name' => 'Demande de relogement', 'description' => 'Si le foyer a fait une demande de relogement ou non', 'export' => 'isRelogement'],
        'DECLARANT_TIERS' => ['name' => 'Déclarant tiers', 'description' => 'Si le signalement a été déposé par un tiers ou non', 'export' => 'isNotOccupant'],
        'NOM_TIERS' => ['name' => 'Nom tiers', 'description' => 'Le nom du tiers déclarant', 'export' => 'nomDeclarant'],
        'EMAIL_TIERS' => ['name' => 'E-mail tiers', 'description' => 'L\'adresse e-mail du tiers déclarant', 'export' => 'emailDeclarant'],
        'STRUCTURE_TIERS' => ['name' => 'Structure tiers', 'description' => 'La structure du tiers déclarant', 'export' => 'structureDeclarant'],
        'LIEN_TIERS' => ['name' => 'Lien tiers occupant', 'description' => 'Le lien du tiers déclarant avec l\'occupant (voisin, proche, pro...)', 'export' => 'lienDeclarantOccupant'],
        'STATUT_VISITE' => ['name' => 'Statut de la visite', 'description' => 'Le statut de la visite (planifiée, terminée, à planifier...)', 'export' => 'interventionStatus'],
        'DATE_VISITE' => ['name' => 'Date de visite', 'description' => 'La date de visite du logement', 'export' => 'dateVisite'],
        'OCCUPANT_PRESENT_VISITE' => ['name' => 'Occupant présent visite', 'description' => 'Si l\'occupant était présent pendant la visite ou non', 'export' => 'isOccupantPresentVisite'],
        'CONCLUSION_VISITE' => ['name' => 'Conclusion de la visite', 'description' => 'La conclusion de la visite (procédures constatées)', 'export' => 'interventionConcludeProcedure'],
        'COMMENTAIRE_VISITE' => ['name' => 'Commentaire de la visite', 'description' => 'Le commentaire laissé par l\'opérateur suite à la visite', 'export' => 'interventionDetails'],
        'DERNIERE_MAJ' => ['name' => 'Dernière MAJ le', 'description' => 'La date de la dernière mise à jour du signalement', 'export' => 'modifiedAt'],
        'DATE_CLOTURE' => ['name' => 'Clôturé le', 'description' => 'La date de clôture du signalement', 'export' => 'closedAt'],
        'MOTIF_CLOTURE' => ['name' => 'Motif de clôture', 'description' => 'Le motif de clôture du signalement', 'export' => 'motifCloture'],
        'GEOLOCALISATION' => ['name' => 'Géolocalisation', 'description' => 'Les coordonnées GPS du logement', 'export' => 'geoloc'],
    ];

    #[Route('/', name: 'back_signalement_list_export', methods: ['GET'])]
    public function index(
        Request $request,
        SignalementManager $signalementManager,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $filters = $options = $request->getSession()->get('filters') ?? ['isImported' => '1'];
        $signalements = $signalementManager->findSignalementAffectationList($user, $options);
        $count_signalements = $signalements['pagination']['total_items'];

        $textFilters = $this->filtersToText($filters);

        return $this->render('back/signalement_export/index.html.twig', [
            'filters' => $textFilters,
            'selectable_cols' => self::SELECTABLE_COLS,
            'count_signalements' => $count_signalements,
        ]);
    }

    private function filtersToText(array $filters): array
    {
        unset($filters['page']);
        unset($filters['maxItemsPerPage']);
        unset($filters['sortBy']);
        unset($filters['orderBy']);

        $result = [];

        foreach ($filters as $filterName => $filterValue) {
            switch ($filterName) {
                case 'isImported':
                    $filterName = 'Signalement importés';
                    break;
                case 'territories':
                    $filterName = 'Territoires';
                    break;
                case 'partners':
                    $filterName = 'Partenaires';
                    break;
                case 'searchterms':
                    $filterName = 'Recherche';
                    break;
                case 'cities':
                    $filterName = 'Ville ou code postal';
                    break;
                case 'statuses':
                    $filterName = 'Statut';
                    break;
                case 'epcis':
                    $filterName = 'EPCI';
                    break;
                case 'procedure':
                    $filterName = 'Procédure suspectée';
                    break;
                case 'dates':
                    $filterName = 'Date de dépôt';
                    break;
                case 'visites':
                    $filterName = 'Visite';
                    break;
                case 'typeDernierSuivi':
                    $filterName = 'Type dernier suivi';
                    break;
                case 'datesDernierSuivi':
                    $filterName = 'Date dernier suivi';
                    break;
                case 'statusAffectation':
                    $filterName = 'Statut Affectation';
                    break;
                case 'closed_affectation':
                    $filterName = 'Affectation fermée';
                    break;
                case 'enfantsM6':
                    $filterName = 'Enfants de moins de 6 ans';
                    break;
                case 'scores':
                    $filterName = 'Criticité';
                    break;
                case 'typeDeclarant':
                    $filterName = 'Type de déclarant';
                    break;
                case 'situation':
                    $filterName = 'Situation';
                    break;
                case 'bailleurSocial':
                    $filterName = 'Bailleur';
                    break;
            }

            if (is_array($filterValue)) {
                $filterValue = implode(', ', $filterValue);
            } elseif (is_a($filterValue, 'App\Entity\Bailleur')) {
                $filterValue = $filterValue->getName();
            } else {
                if ('1' == $filterValue) {
                    $filterValue = 'Oui';
                }
                if ('0' == $filterValue) {
                    $filterValue = 'Non';
                }
            }

            if (!empty($filterValue)) {
                $result[$filterName] = $filterValue;
            }
        }

        return $result;
    }

    #[Route('/file', name: 'back_signalement_list_export_file', methods: ['POST'])]
    public function exportFile(
        Request $request,
        MessageBusInterface $messageBus
    ): RedirectResponse {
        $format = $request->get('file-format');
        if (!in_array($format, ['csv', 'xlsx'])) {
            $this->addFlash('error', "Merci de sélectionner le format de l'export.");

            return $this->redirectToRoute('back_signalement_list_export');
        }

        /** @var User $user */
        $user = $this->getUser();
        $filters = $request->getSession()->get('filters') ?? [];
        $selectedColumns = $request->get('cols') ?? [];

        $message = (new ListExportMessage())
            ->setUserId($user->getId())
            ->setFormat($format)
            ->setFilters($filters)
            ->setSelectedColumns($selectedColumns);

        $messageBus->dispatch($message);

        $this->addFlash(
            'success',
            \sprintf(
                'L\'export vous sera envoyé par e-mail à l\'adresse suivante : %s. Il arrivera d\'ici quelques minutes. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                $user->getEmail()
            )
        );

        return $this->redirectToRoute('back_signalement_list_export');
    }

    #[Route('/csv', name: 'back_signalement_list_export_old_csv')]
    public function exportCsv(
        Request $request,
        SignalementExportLoader $signalementExportLoader
    ): RedirectResponse|StreamedResponse {
        /** @var User $user */
        $user = $this->getUser();
        $filters = $request->getSession()->get('filters');
        try {
            $response = new StreamedResponse();
            $response->setCallback(function () use ($signalementExportLoader, $filters, $user) {
                $signalementExportLoader->load($user, $filters);
            });

            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                'export-histologe-'.date('dmY').'.csv'
            );
            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition', $disposition);

            return $response;
        } catch (\ErrorException $e) {
            $this->addFlash('error', 'Problème d\'identification de votre demande. Merci de réessayer.');
            throw new \Exception('Erreur lors de l\'export du fichier par l\'user "'.$user->getId().'" : '.$e->getMessage().' - '.print_r($filters, true));
        }
    }
}
