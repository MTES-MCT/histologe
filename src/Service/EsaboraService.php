<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Suivi;
use App\Factory\DossierMessageFactory;
use App\Messenger\Message\DossierMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class EsaboraService
{
    public const ESABORA_WAIT = 'A traiter';
    public const ESABORA_ACCEPTED = 'Importé';
    public const ESABORA_REFUSED = 'Non importé';
    public const ESABORA_CLOSED = 'terminé';

    public function __construct(
        private HttpClientInterface $client,
        private EntityManagerInterface $em,
        private DossierMessageFactory $dossierFactory
    ) {
    }

    public function pushDossier(DossierMessage $dossierMessage): ResponseInterface
    {
        $url = $dossierMessage->getUrl();
        $token = $dossierMessage->getToken();
        $payload = [
            'treatmentName' => 'Import HISTOLOGE',
            'fieldList' => $dossierMessage->preparePayload(),
        ];
        $response = $this->client->request('POST', $url.'/modbdd/?task=doTreatment', [
                'headers' => [
                    'Authorization: Bearer '.$token,
                    'Content-Type: application/json',
                ],
                'body' => json_encode($payload, \JSON_THROW_ON_ERROR),
            ]
        );

        return $response;
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

    public function synchronizeDossier(Affectation $affectation)
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
