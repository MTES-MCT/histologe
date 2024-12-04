<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Messenger\Message\SuiviSummariesMessage;
use App\Repository\SignalementRepository;
use App\Service\HtmlCleaner;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\TimezoneProvider;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsMessageHandler]
class SuiviSummariesMessageHandler
{
    private const string API_ALBERT_URL = 'https://albert.api.etalab.gouv.fr/v1/chat/completions';

    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly LoggerInterface $logger,
        private readonly SignalementRepository $signalementRepository,
        private readonly HttpClientInterface $httpClient,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function __invoke(SuiviSummariesMessage $suiviSummariesMessage): void
    {
        try {
            $user = $suiviSummariesMessage->getUser();
            $spreadsheet = $this->buildSpreadsheet($suiviSummariesMessage);

            $writer = new Csv($spreadsheet);
            $timezone = $user->getTerritory()?->getTimezone() ?? TimezoneProvider::TIMEZONE_EUROPE_PARIS;
            $datetimeStr = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone($timezone))->format('dmY-Hi');
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
     * TODO : export to an export service
     */
    private function buildSpreadsheet(SuiviSummariesMessage $suiviSummariesMessage): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $headers = ['Lien', 'Résumé'];
        $sheetData = [$headers];

        /*
        case 'reponse-usager':
            return 'Relancés automatiquement, avec réponse usager';
        case 'dernier-suivi-3-semaines':
            return 'Dernier suivi non-automatique et non-RT, mais sans suivi depuis 3 semaines';
            */

        $list = $this->signalementRepository->findBy(
            criteria: [
                'statut' => Signalement::STATUS_ACTIVE,
                'territory' => $suiviSummariesMessage->getTerritory()
            ],
            orderBy: ['createdAt' => 'DESC'],
            limit: $suiviSummariesMessage->getCount(),
        );

        /** @var Signalement $signalement */
        foreach ($list as $signalement) {
            $rowArray = [
                $signalement->getUuid(),
                $this->getSummaryFromSignalement($signalement, $suiviSummariesMessage),
            ];
            $sheetData[] = $rowArray;
        }

        $sheet->fromArray($sheetData);

        return $spreadsheet;
    }

    /**
     * TODO : export to an Albert API communication service
     */
    private function getSummaryFromSignalement(Signalement $signalement, SuiviSummariesMessage $suiviSummariesMessage): ?string
    {
        $messages = [];

        $suivis = $signalement->getSuivis();
        $countSuivis = \count($suivis);
        for ($iSuivi = $countSuivis - 1; $iSuivi >= 0; --$iSuivi) {
            /** @var Suivi $suivi */
            $suivi = $suivis[$iSuivi];
            if (Suivi::TYPE_AUTO !== $suivi->getType() && Suivi::TYPE_TECHNICAL !== $suivi->getType()) {
                $suiviDescription = HtmlCleaner::clean($suivi->getDescription());
                $messages[] = $this->getAlbertMessage($suiviSummariesMessage->getPrompt());
                $messages[] = $this->getAlbertMessage('Message du '.$suivi->getCreatedAt()->format('d/m/Y').' : '.$suiviDescription);
                break;
            }
        }

        if (empty($messages)) {
            return null;
        }

        $payload = [
            'messages' => $messages,
            'model' => 'AgentPublic/llama3-instruct-8b',
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
