<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Criticite;
use App\Entity\Suivi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EsaboraService
{
    public const ESABORA_WAIT = 'A traiter';
    public const ESABORA_ACCEPTED = 'Importé';
    public const ESABORA_REFUSED = 'Non importé';
    public const ESABORA_CLOSED = 'terminé';

    private EntityManagerInterface $em;
    private ParameterBagInterface $params;
    private string $commentaire;

    public function __construct(ParameterBagInterface $parameterBag, EntityManagerInterface $entityManager, string $commentaire = '')
    {
        $this->em = $entityManager;
        $this->commentaire = $commentaire;
        $this->params = $parameterBag;
    }

    public function post(Affectation $affectation)
    {
        $url = $affectation->getPartner()->getEsaboraUrl();
        $token = $affectation->getPartner()->getEsaboraToken();
        $signalement = $affectation->getSignalement();
        $this->commentaire = 'Points signalés:\n';
        $signalement->getCriticites()->filter(function (Criticite $criticite) {
            $criticiteLabel = match ($criticite->getScore()) {
                1 => 'moyen',
                2 => 'grave',
                3 => 'très grave',
            };
            $this->commentaire .= '\n'.$criticite->getCritere()->getLabel().' => Etat '.$criticiteLabel;
        });
        $this->commentaire .= '\nPropriétaire averti: '.$signalement->getIsProprioAverti() ? 'OUI' : 'NON';
        $this->commentaire .= '\nAdultes: '.$signalement->getNbAdultes().' Adultes';
        $this->commentaire .= '\n'.$signalement->getNbEnfantsM6() + $signalement->getNbEnfantsP6().' Enfants';
        $signalement->getAffectations()->filter(function (Affectation $affectation) {
            $affectationLabel = match ($affectation->getStatut()) {
                Affectation::STATUS_WAIT => 'En attente...',
                Affectation::STATUS_ACCEPTED => 'Accepté',
                Affectation::STATUS_REFUSED => 'Refusé',
                Affectation::STATUS_CLOSED => 'Cloturé',
            };
            $this->commentaire .= '\n'.$affectation->getPartner()->getNom().' => '.$affectationLabel;
        });
        $observationsPj = '';
        $documentsPj = [];
        foreach ($signalement->getDocuments() as $document) {
            $src = $this->params->get('uploads_dir').$document['file'];
            $observationsPj .= $document['titre'];
            $documentsPj[] = [
                'documentName' => $document['titre'],
                'documentSize' => filesize($src),
                'documentContent' => base64_encode(file_get_contents($src)),
            ];
        }
        foreach ($signalement->getPhotos() as $photo) {
            $src = $this->params->get('uploads_dir').$photo['file'];
            $documentsPj[] = [
                'documentName' => 'Image téléversée',
                'documentSize' => filesize($src),
                'documentContent' => base64_encode(file_get_contents($src)),
            ];
        }
        $response = $this->curl('POST', $url.'/modbdd/?task=doTreatment', $token, [
            'treatmentName' => 'Import HISTOLOGE',
            'fieldList' => [
                [
                    'fieldName' => 'Référence_Histologe',
                    'fieldValue' => $signalement->getUuid(),
                ],
                [
                    'fieldName' => 'Usager_Nom',
                    'fieldValue' => $signalement->getNomOccupant(),
                ],
                [
                    'fieldName' => 'Usager_Prénom',
                    'fieldValue' => $signalement->getPrenomOccupant(),
                ],
                [
                    'fieldName' => 'Usager_Mail',
                    'fieldValue' => $signalement->getMailOccupant(),
                ],
                [
                    'fieldName' => 'Usager_Téléphone',
                    'fieldValue' => $signalement->getTelOccupant(),
                ],
                [
                    'fieldName' => 'Usager_Numéro',
                    'fieldValue' => '',
                ],
                [
                    'fieldName' => 'Usager_Nom_Rue',
                    'fieldValue' => $signalement->getAdresseOccupant(),
                ],
                [
                    'fieldName' => 'Usager_Adresse2',
                    'fieldValue' => '',
                ],
                [
                    'fieldName' => 'Usager_CodePostal',
                    'fieldValue' => $signalement->getCpOccupant(),
                ],
                [
                    'fieldName' => 'Usager_Ville',
                    'fieldValue' => $signalement->getVilleOccupant(),
                ],
                [
                    'fieldName' => 'Adresse_Numéro',
                    'fieldValue' => '',
                ],
                [
                    'fieldName' => 'Adresse_Nom_Rue',
                    'fieldValue' => $signalement->getAdresseOccupant(),
                ],
                [
                    'fieldName' => 'Adresse_CodePostal',
                    'fieldValue' => $signalement->getCpOccupant(),
                ],
                [
                    'fieldName' => 'Adresse_Ville',
                    'fieldValue' => $signalement->getVilleOccupant(),
                ],
                [
                    'fieldName' => 'Adresse_Etage',
                    'fieldValue' => $signalement->getEtageOccupant(),
                ],
                [
                    'fieldName' => 'Adresse_Porte',
                    'fieldValue' => $signalement->getNumAppartOccupant(),
                ],
                [
                    'fieldName' => 'Adresse_Latitude',
                    'fieldValue' => $signalement->getGeoloc()['lat'] ?? '',
                ],
                [
                    'fieldName' => 'Adresse_Longitude',
                    'fieldValue' => $signalement->getGeoloc()['lng'] ?? '',
                ],
                [
                    'fieldName' => 'Dossier_Ouverture',
                    'fieldValue' => $signalement->getCreatedAt()->format('d/m/Y'),
                ],
                [
                    'fieldName' => 'Dossier_Commentaire',
                    'fieldValue' => $this->commentaire,
                ],
                [
                    'fieldName' => 'PJ_Observations',
                    'fieldValue' => $observationsPj,
                ],
                [
                    'fieldName' => 'PJ_Documents',
                    'fieldDocumentUpdate' => 1,
                    'fieldValue' => $documentsPj,
                ],
            ],
        ]);
        echo $response;
    }

    private function curl($method, $url, $token, $body = [])
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            \CURLOPT_URL => $url,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_MAXREDIRS => 10,
            \CURLOPT_TIMEOUT => 0,
            \CURLOPT_FOLLOWLOCATION => true,
            \CURLOPT_HTTP_VERSION => \CURL_HTTP_VERSION_1_1,
            \CURLOPT_CUSTOMREQUEST => $method,
            \CURLOPT_HTTPHEADER => [
                'Authorization: Bearer '.$token,
            ],
            \CURLOPT_POSTFIELDS => json_encode($body, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES),
        ]);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function get(Affectation $affectation)
    {
        // ["SAS_Référence","SAS_Etat","Doss_ID","Doss_Numéro","Doss_Statut_Abrégé","Doss_Statut","Doss_Etat","Doss_Cloture", "Doss_Type", "Doss_Problématique"]
        $url = $affectation->getPartner()->getEsaboraUrl();
        $token = $affectation->getPartner()->getEsaboraToken();

        $response = $this->curl('POST', $url.'/mult/?task=doSearch', $token, [
            'searchName' => 'WS_ETAT_DOSSIER_SAS',
            'criterionList' => [
                [
                    'criterionName' => 'SAS_Référence',
                    'criterionValueList' => [
                        $affectation->getSignalement()->getUuid(),
                    ],
                ],
            ],
        ]);
        $response = json_decode($response, true);
        var_dump($response);
        $definition = 'mis à jour';
        $change = false;
        $data = $response['rowList'][0]['columnDataList'];
        $currentStatus = $affectation->getStatut();
        switch ($data[1]) {
            case self::ESABORA_WAIT:
                if (Affectation::STATUS_ACCEPTED !== $currentStatus) {
                    $affectation->setStatut(Affectation::STATUS_WAIT);
                    $definition = 'remis en attente via Esabora';
                    $change = true;
                }
                break;
            case self::ESABORA_ACCEPTED:
                if (Affectation::STATUS_ACCEPTED !== $currentStatus) {
                    $affectation->setStatut(Affectation::STATUS_ACCEPTED);
                    $definition = 'accepté via Esabora';
                    $change = true;
                }
                break;
            case self::ESABORA_REFUSED:
                if (Affectation::STATUS_REFUSED !== $currentStatus) {
                    $affectation->setStatut(Affectation::STATUS_REFUSED);
                    $definition = 'refusé via Esabora';
                    $change = true;
                }
                break;
        }
        self::ESABORA_CLOSED === $data[6] ? Affectation::STATUS_CLOSED === $affectation->getStatut() ?? $affectation->setStatut(Affectation::STATUS_CLOSED) : null && $definition = 'cloturé via Esabora' && $change = true;
        $this->em->persist($affectation);
        if ($change) {
            $affectation->setAnsweredBy($affectation->getPartner()->getUsers()->first());
            $suivi = new Suivi();
            $suivi->setDescription('Signalement <b>'.$definition.'</b> par '.$affectation->getPartner()->getNom());
            $suivi->setSignalement($affectation->getSignalement());
            $suivi->setCreatedBy($affectation->getPartner()->getUsers()->first());
            $this->em->persist($suivi);
        }
        $this->em->flush();
    }
}
