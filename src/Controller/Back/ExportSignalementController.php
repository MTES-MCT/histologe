<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Service\Signalement\Export\SignalementExportLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/bo/export/signalement')]
class ExportSignalementController extends AbstractController
{
    private const SELECTABLE_COLS = [
        'TEL_SEC' => [ 'name' => 'Téléphone sec.', 'description' => 'Numéro de téléphone secondaire de l\'occupant du logement' ],
        'INSEE' => [ 'name' => 'Code INSEE', 'description' => 'Le code INSEE de la commune du signalement' ],
        'ETAGE' => [ 'name' => 'Etage', 'description' => 'L\'étage du logement' ],
        'ESCALIER' => [ 'name' => 'Escalier', 'description' => 'Le numéro d\'escalier du logement' ],
        'APPARTEMENT' => [ 'name' => 'Appartement', 'description' => 'Le numéro d\'appartement du logement' ],
        'COMP_ADRESSE' => [ 'name' => 'Complément d\'adresse', 'description' => 'Le complément d\'adresse du logement' ],
        'CRITICITE' => [ 'name' => 'Criticité au dépôt', 'description' => 'Score de criticité calculé automatiquement au dépôt du signalement' ],
        'ETIQUETTES' => [ 'name' => 'Etiquettes', 'description' => 'Les étiquettes ajoutées au signalement' ],
        'PHOTOS' => [ 'name' => 'Photos', 'description' => 'Le nom des fichiers photo ajoutés au signalement' ],
        'DOCUMENTS' => [ 'name' => 'Documents', 'description' => 'Le nom des documents ajoutés au signalement' ],
        'PROPRIETAIRE_AVERTI' => [ 'name' => 'Propriétaire averti', 'description' => 'Si le propriétaire a été averti ou non de la situation' ],
        'NB_PERSONNES' => [ 'name' => 'Nb personnes', 'description' => 'Le nombre de personnes occupant le logement' ],
        'MOINS_6_ANS' => [ 'name' => 'Enfants -6 ans', 'description' => 'Si oui ou non il y a des enfants de - de 6 ans dans le logement' ],
        'NUM_ALLOCATAIRE' => [ 'name' => 'Numéro allocataire', 'description' => 'Le numéro d\'allocataire de l\'occupant' ],
        'NATURE_LOGEMENT' => [ 'name' => 'Nature du logement', 'description' => 'La nature du logement (maison, appartement, autre)' ],
        'SUPERFICIE' => [ 'name' => 'Superficie', 'description' => 'La superficie du logement en m²' ],
        'NOM_BAILLEUR' => [ 'name' => 'Nom du bailleur', 'description' => 'Le nom du bailleur du logement' ],
        'PREAVIS_DEPART' => [ 'name' => 'Préavis de départ', 'description' => 'Si le foyer a déposé un préavis de départ ou non' ],
        'DEMANDE_RELOGEMENT' => [ 'name' => 'Demande de relogement', 'description' => 'Si le foyer a fait une demande de relogement ou non' ],
        'DECLARANT_TIERS' => [ 'name' => 'Déclarant tiers', 'description' => 'Si le signalement a été déposé par un tiers ou non' ],
        'NOM_TIERS' => [ 'name' => 'Nom tiers', 'description' => 'Le nom du tiers déclarant' ],
        'EMAIL_TIERS' => [ 'name' => 'E-mail tiers', 'description' => 'L\'adresse e-mail du tiers déclarant' ],
        'STRUCTURE_TIERS' => [ 'name' => 'Structure tiers', 'description' => 'La structure du tiers déclarant' ],
        'LIEN_TIERS' => [ 'name' => 'Lien avec occupant', 'description' => 'Le lien du tiers déclarant avec l\'occupant (voisin, proche, pro...)' ],
        'STATUT_VISITE' => [ 'name' => 'Statut de la visite', 'description' => 'Le statut de la visite (planifiée, terminée, à planifier...)' ],
        'DATE_VISITE' => [ 'name' => 'Date de visite', 'description' => 'La date de visite du logement' ],
        'OCCUPANT_PRESENT_VISITE' => [ 'name' => 'Occupant présent visite', 'description' => 'Si l\'occupant était présent pendant la visite ou non' ],
        'CONCLUSION_VISITE' => [ 'name' => 'Conclusion de la visite', 'description' => 'La conclusion de la visite (procédures constatées)' ],
        'COMMENTAIRE_VISITE' => [ 'name' => 'Commentaire de la visite', 'description' => 'Le commentaire laissé par l\'opérateur suite à la visite' ],
        'DERNIERE_MAJ' => [ 'name' => 'Dernière MAJ le', 'description' => 'La date de la dernière mise à jour du signalement' ],
        'DATE_CLOTURE' => [ 'name' => 'Clôturé le', 'description' => 'La date de clôture du signalement' ],
        'MOTIF_CLOTURE' => [ 'name' => 'Motif de clôture', 'description' => 'Le motif de clôture du signalement' ],
        'GEOLOCALISATION' => [ 'name' => 'Géolocalisation', 'description' => 'Les coordonnées GPS du logement' ],
    ];

    #[Route('/', name: 'back_signalement_list_export', methods: ['GET'])]
    public function index(
        Request $request,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $filters = $request->getSession()->get('filters');

        return $this->render('back/signalement_export/index.html.twig', [
            'filters' => $filters,
            'selectable_cols' => self::SELECTABLE_COLS
        ]);
    }

    #[Route('/csv', name: 'back_signalement_list_export_file', methods: ['POST'])]
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
