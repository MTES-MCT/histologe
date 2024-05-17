<?php

namespace App\Service\Idoss;

use App\Entity\Partner;
use App\Messenger\Message\Idoss\DossierMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ContainerBagInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class IdossService
{
    public const TYPE_SERVICE = 'idoss';
    public const ACTION_PUSH_DOSSIER = 'push_dossier';
    public const CODE_INSEE_BASSIN_VIE_MARSEILLE = '13055';
    private const AUTHENTICATE_ENDPOINT = '/api/Utilisateur/authentification';
    private const CREATE_DOSSIER_ENDPOINT = '/api/EtatCivil/creatDossHistologe';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly ContainerBagInterface $params,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function pushDossier(DossierMessage $dossierMessage): ResponseInterface
    {
        $token = $this->getToken($dossierMessage);
        $url = $dossierMessage->getUrl().self::CREATE_DOSSIER_ENDPOINT;

        $payload = [
            'user' => $this->params->get('idoss_username'),
            'Dossier' => [
                'UUIDDossier' => $dossierMessage->getSignalementUuid(),
                'dateDepotSignalement' => $dossierMessage->getDateDepotSignalement(),
                'declarant' => $dossierMessage->getDeclarant(),
                'occupant' => $dossierMessage->getOccupant(),
                'proprietaire' => $dossierMessage->getProprietaire(),
                'bailEncours' => $dossierMessage->getBailEnCour(),
                'construitAv1949' => $dossierMessage->getConstruitAv1949(),
            ],
            'Etape' => $dossierMessage->getEtape(),
        ];
        if ($dossierMessage->getAdresse1()) {
            $payload['Dossier']['adresse1'] = $dossierMessage->getAdresse1();
        }
        if ($dossierMessage->getAdresse2()) {
            $payload['Dossier']['adresse2'] = $dossierMessage->getAdresse2();
        }
        if ($dossierMessage->getDescriptionProblemes()) {
            $payload['Dossier']['descriptionProblemes'] = $dossierMessage->getDescriptionProblemes();
        }
        if (\count($dossierMessage->getPj())) {
            $payload['Dossier']['pj'] = $dossierMessage->getPj();
        }
        if ($dossierMessage->getNumAllocataire()) {
            $payload['Dossier']['numAllocataire'] = $dossierMessage->getNumAllocataire();
        }
        if ($dossierMessage->getMontantAllocation()) {
            $payload['Dossier']['montantAllocation'] = $dossierMessage->getMontantAllocation();
        }
        if ($dossierMessage->getDateEntreeLogement()) {
            $payload['Dossier']['dateEntreeLogement'] = $dossierMessage->getDateEntreeLogement();
        }
        if ($dossierMessage->getMontantLoyer()) {
            $payload['Dossier']['montantLoyer'] = $dossierMessage->getMontantLoyer();
        }
        if ($dossierMessage->getNbrPieceLogement()) {
            $payload['Dossier']['nbrPieceLogement'] = $dossierMessage->getNbrPieceLogement();
        }
        if ($dossierMessage->getNbrEtages()) {
            $payload['Dossier']['nbrEtages'] = $dossierMessage->getNbrEtages();
        }

        return $this->request($url, $payload, $token);
    }

    public function getToken(DossierMessage $dossierMessage): string
    {
        if ($dossierMessage->getToken() && $dossierMessage->getTokenExpirationDate() && $dossierMessage->getTokenExpirationDate() > new \DateTime()) {
            return $dossierMessage->getToken();
        }

        $url = $dossierMessage->getUrl().self::AUTHENTICATE_ENDPOINT;
        $payload = [
            'username' => $this->params->get('idoss_username'),
            'password' => $this->params->get('idoss_password'),
        ];

        $response = $this->request($url, $payload);
        if (200 !== $response->getStatusCode()) {
            throw new \Exception('Token not found : '.$response->getContent(throw: false));
        }
        $jsonResponse = json_decode($response->getContent());
        if (isset($jsonResponse->token) && isset($jsonResponse->expirationDate)) {
            $partner = $this->entityManager->getRepository(Partner::class)->find($dossierMessage->getPartnerId());
            $partner->setIdossToken($jsonResponse->token);
            $partner->setIdossTokenExpirationDate(new \DateTime($jsonResponse->expirationDate));
            $this->entityManager->flush();

            return $jsonResponse->token;
        }
        throw new \Exception('Token not found : '.$response->getContent(throw: false));
    }

    protected function request(string $url, array $payload, ?string $token = null): ResponseInterface
    {
        $options = [
            'headers' => [
                'Content-Type: application/json',
            ],
            'body' => json_encode($payload),
        ];
        if ($token) {
            $options['headers'][] = 'Authorization: Bearer '.$token;
        }
        $response = $this->client->request('POST', $url, $options);

        return $response;
    }
}
