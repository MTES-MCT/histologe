<?php

namespace App\Service\Esabora;

use App\Entity\Affectation;
use App\Manager\AffectationManager;
use App\Messenger\Message\DossierMessage;
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
        private AffectationManager $affectationManager,
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

        return $this->client->request('POST', $url.'/modbdd/?task=doTreatment', [
                'headers' => [
                    'Authorization: Bearer '.$token,
                    'Content-Type: application/json',
                ],
                'body' => json_encode($payload, \JSON_THROW_ON_ERROR),
            ]
        );
    }

    public function getStateDossier(Affectation $affectation): DossierResponse
    {
        list($url, $token) = $affectation->getPartner()->getEsaboraCredential();
        $payload = [
            'searchName' => 'WS_ETAT_DOSSIER_SAS',
            'criterionList' => [
                [
                    'criterionName' => 'SAS_Référence',
                    'criterionValueList' => [
                        $affectation->getSignalement()->getUuid(),
                    ],
                ],
            ],
        ];

        $response = $this->client->request('POST', $url.'/mult/?task=doSearch', [
             'headers' => [
                 'Authorization: Bearer '.$token,
                 'Content-Type: application/json',
             ],
                 'body' => json_encode($payload, \JSON_THROW_ON_ERROR),
            ]
        );

        return new DossierResponse(
            200 === $response->getStatusCode() ? $response->toArray() : [],
            $response->getStatusCode());
    }
}
