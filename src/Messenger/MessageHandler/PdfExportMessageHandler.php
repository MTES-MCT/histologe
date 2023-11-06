<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\PdfExportMessage;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Export\SignalementExportPdf;
use App\Service\UploadHandlerService;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;

#[AsMessageHandler]
class PdfExportMessageHandler
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly SignalementExportPdf $signalementExportPdf,
        private readonly Environment $twig,
        private readonly SignalementRepository $signalementRepository,
        private readonly ParameterBagInterface $parameterBag,
        private readonly UploadHandlerService $uploadHandlerService,
    ) {
    }

    public function __invoke(PdfExportMessage $pdfExportMessage): void
    {
        $criticitesFormatted = [];
        $signalement = $this->signalementRepository->find($pdfExportMessage->getSignalementId());

        foreach ($signalement->getCriticites() as $criticite) {
            $situationLabel = $criticite->getCritere()->getSituation()->getLabel();
            $critereLabel = $criticite->getCritere()->getLabel();
            $criticitesFormatted[$situationLabel][$critereLabel] = $criticite;
        }

        $htmlContent = $this->twig->render('pdf/signalement.html.twig', [
            'signalement' => $signalement,
            'situations' => $criticitesFormatted,
        ]);

        $tmpFilename = $this->signalementExportPdf->generateToTempFolder(
            $signalement,
            $htmlContent,
            $this->parameterBag->get('export_options')
        );

        $filename = $this->uploadHandlerService->uploadFromFilename($tmpFilename);

        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_PDF_EXPORT,
                to: $pdfExportMessage->getUserEmail(),
                signalement: $signalement,
                params: [
                    'filename' => 'export-pdf-signalement-'.$signalement->getUuid().'.pdf',
                ]
            )
        );
    }
}
