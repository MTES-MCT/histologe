<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\PdfExportMessage;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Export\SignalementExportPdf;
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
    ) {
    }

    public function __invoke(PdfExportMessage $pdfExportMessage): void
    {
        $signalementId = $pdfExportMessage->getSignalementId();
        $signalement = $this->signalementRepository->find($signalementId);

        $html = $this->twig->render('pdf/signalement.html.twig', [
            'signalement' => $signalement,
            'situations' => $pdfExportMessage->getCriticites(),
        ]);

        $pdfContent = $this->signalementExportPdf->generatePdf($html, $pdfExportMessage->getOptions());
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_PDF_EXPORT,
                to: $pdfExportMessage->getUserEmail(),
                signalement: $signalement,
                attachment: ['content' => $pdfContent, 'filename' => $signalement->getReference().'.pdf'],
            )
        );
    }
}
