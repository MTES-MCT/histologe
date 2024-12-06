<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\SuiviSummariesMessage;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Service\HtmlCleaner;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class SuiviSummariesMessageHandler
{
    private const string API_ALBERT_URL = 'https://albert.api.etalab.gouv.fr/v1/chat/completions';

    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly LoggerInterface $logger,
        private readonly SignalementRepository $signalementRepository,
        private readonly UserRepository $userRepository,
        private readonly HttpClientInterface $httpClient,
        private readonly UrlGeneratorInterface $urlGenerator,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function __invoke(SuiviSummariesMessage $suiviSummariesMessage): void
    {
        try {
            $user = $suiviSummariesMessage->getUser();
            $spreadsheet = $this->buildSpreadsheet($suiviSummariesMessage);

            $writer = new Csv($spreadsheet);
            $datetimeStr = (new \DateTimeImmutable())->format('dmY-Hi');
            $filename = 'resumes-suivis-'.$user->getId().'-'.$datetimeStr.'.csv';
            $tmpFilepath = $this->parameterBag->get('uploads_tmp_dir').$filename;
            $writer->save($tmpFilepath);

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_SUIVI_SUMMARIES_EXPORT,
                    to: $suiviSummariesMessage->getUser()->getEmail(),
                    attachment: $tmpFilepath
                )
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'The export of suivi summaries failed for the following reason : %s',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * TODO : move to an export service.
     */
    private function buildSpreadsheet(SuiviSummariesMessage $suiviSummariesMessage): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Référence du signalement', 'Lien vers le signalement', 'Date du dernier suivi', 'Auteur du dernier suivi', 'Résumé du dernier suivi', 'Contenu du dernier suivi'];
        $sheetData = [$headers];

        $list = [];
        switch ($suiviSummariesMessage->getQuerySignalement()) {
            case 'reponse-usager':
                $list = $this->signalementRepository->findSignalementsLastSuiviWithSuiviAuto(
                    territory: $suiviSummariesMessage->getTerritory(),
                    limit: $suiviSummariesMessage->getCount(),
                );
                break;
            case 'dernier-suivi-20-jours':
                $list = $this->signalementRepository->findSignalementsLastSuiviByPartnerOlderThan(
                    territory: $suiviSummariesMessage->getTerritory(),
                    limit: $suiviSummariesMessage->getCount(),
                    nbDays: 20,
                );
                break;
            default:
                break;
        }

        /** @var array $signalementResult */
        foreach ($list as $signalementResult) {
            $cleanLastSuiviDescription = HtmlCleaner::clean($signalementResult['dernier_suivi_description']);
            $userAuthorSuivi = $this->userRepository->find($signalementResult['dernier_suivi_created_by']);
            $userAuthorSuiviStr = $userAuthorSuivi->getPrenom().' '.$userAuthorSuivi->getNom();
            if (!empty($userAuthorSuivi->getPartner())) {
                $userAuthorSuiviStr .= ' ('.$userAuthorSuivi->getPartner()->getNom().')';
            }
            $rowArray = [
                $signalementResult['reference'],
                $this->urlGenerator->generate('back_signalement_view', ['uuid' => $signalementResult['uuid']], UrlGeneratorInterface::ABSOLUTE_URL),
                $signalementResult['dernier_suivi_date'],
                $userAuthorSuiviStr,
                $this->getSummaryFromSignalement($cleanLastSuiviDescription, $suiviSummariesMessage),
                $cleanLastSuiviDescription,
            ];
            $sheetData[] = $rowArray;
        }

        $sheet->fromArray($sheetData);

        return $spreadsheet;
    }

    /**
     * TODO : move to an Albert API communication service.
     */
    private function getSummaryFromSignalement(string $cleanLastSuiviDescription, SuiviSummariesMessage $suiviSummariesMessage): ?string
    {
        $messages = [];

        if (!empty($cleanLastSuiviDescription)) {
            $messages[] = $this->getAlbertMessage($suiviSummariesMessage->getPrompt());
            $messages[] = $this->getAlbertMessage($cleanLastSuiviDescription);
        } else {
            return null;
        }

        $payload = [
            'messages' => $messages,
            'model' => $suiviSummariesMessage->getModel(),
            'stream' => false,
            'n' => 1,
        ];
        $token = $this->parameterBag->get('albert_api_key');
        $options = [
            'headers' => [
                'Authorization: Bearer '.$token,
                'Content-Type: application/json',
            ],
            'body' => json_encode($payload),
        ];
        $response = $this->httpClient->request('POST', self::API_ALBERT_URL, $options);
        $content = json_decode($response->getContent());

        return $content->choices[0]->message->content;
    }

    private function getAlbertMessage(string $message): array
    {
        return ['role' => 'user', 'content' => $message];
    }
}
