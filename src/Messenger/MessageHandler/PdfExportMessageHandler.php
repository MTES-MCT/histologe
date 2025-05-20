<?php

namespace App\Messenger\MessageHandler;

use App\Entity\Intervention;
use App\Messenger\Message\PdfExportMessage;
use App\Repository\InterventionRepository;
use App\Repository\SignalementRepository;
use App\Service\Mailer\NotificationMail;
use App\Service\Mailer\NotificationMailerRegistry;
use App\Service\Mailer\NotificationMailerType;
use App\Service\Signalement\Export\SignalementExportPdfGenerator;
use App\Service\Signalement\SignalementDesordresProcessor;
use App\Service\UploadHandlerService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;

#[AsMessageHandler]
class PdfExportMessageHandler
{
    public function __construct(
        private readonly NotificationMailerRegistry $notificationMailerRegistry,
        private readonly SignalementExportPdfGenerator $signalementExportPdfGenerator,
        private readonly Environment $twig,
        private readonly SignalementRepository $signalementRepository,
        private readonly InterventionRepository $interventionRepository,
        private readonly UploadHandlerService $uploadHandlerService,
        private readonly SignalementDesordresProcessor $signalementDesordresProcessor,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(PdfExportMessage $pdfExportMessage): void
    {
        try {
            $signalement = $this->signalementRepository->find($pdfExportMessage->getSignalementId());
            $infoDesordres = $this->signalementDesordresProcessor->process($signalement);
            $listQualificationStatusesLabelsCheck = [];
            if (null !== $signalement->getSignalementQualifications()) {
                foreach ($signalement->getSignalementQualifications() as $qualification) {
                    if (!$qualification->isPostVisite()) {
                        $listQualificationStatusesLabelsCheck[] = $qualification->getStatus()->label();
                    }
                }
            }

            $listConcludeProcedures = [];
            if (null !== $signalement->getInterventions()) {
                foreach ($signalement->getInterventions() as $intervention) {
                    if (Intervention::STATUS_DONE == $intervention->getStatus()) {
                        $listConcludeProcedures = array_merge(
                            $listConcludeProcedures,
                            $intervention->getConcludeProcedure()
                        );
                    }
                }
            }
            $listConcludeProcedures = array_unique(array_map(function ($concludeProcedure) {
                return $concludeProcedure->label();
            }, $listConcludeProcedures));

            $visites = $this->interventionRepository->getOrderedVisitesForSignalement($signalement);

            $htmlContent = $this->twig->render('pdf/signalement.html.twig', [
                'signalement' => $signalement,
                'situations' => $infoDesordres['criticitesArranged'],
                'listConcludeProcedures' => $listConcludeProcedures,
                'listQualificationStatusesLabelsCheck' => $listQualificationStatusesLabelsCheck,
                'visites' => $visites,
                'isForUsager' => $pdfExportMessage->isForUsager(),
            ]);

            $tmpFilename = $this->signalementExportPdfGenerator->generateToTempFolder(
                $signalement,
                $htmlContent,
                $pdfExportMessage->isForUsager(),
            );

            $filename = $this->uploadHandlerService->uploadFromFilename($tmpFilename);

            $this->notificationMailerRegistry->send(
                new NotificationMail(
                    type: NotificationMailerType::TYPE_PDF_EXPORT,
                    to: $pdfExportMessage->getUserEmail(),
                    signalement: $signalement,
                    params: [
                        'filename' => $filename,
                        'isForUsager' => $pdfExportMessage->isForUsager(),
                    ]
                )
            );
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    'The PDF generation of the signalement (%s) failed for the following reason : %s',
                    $pdfExportMessage->getSignalementId(),
                    $exception->getMessage()
                )
            );
        }
    }
}
