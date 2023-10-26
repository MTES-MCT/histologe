<?php

namespace App\Messenger\MessageHandler;

use App\Messenger\Message\PdfExportMessage;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Export\SignalementExportPdf;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PdfExportMessageHandler
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly SignalementExportPdf $signalementExportPdf,
    ) {
    }

    public function __invoke(PdfExportMessage $pdfExportMessage): void
    {
        $signalement = $pdfExportMessage->getSignalement();
        $user = $pdfExportMessage->getUser();
        $pdfContent = $this->signalementExportPdf->generatePdf($pdfExportMessage->getHtml(), $pdfExportMessage->getOptions());
        $this->notificationMailerRegistry->send(
            new NotificationMail(
                type: NotificationMailerType::TYPE_PDF_EXPORT,
                to: $user->getEmail(),
                signalement: $signalement,
                attachment: ['content' => $pdfContent, 'filename' => $signalement->getReference().'.pdf'],
            )
        );
    }
}
