<?php

namespace App\Controller\Back;

use App\Dto\Request\Signalement\GenerateFileZipSelectionRequest;
use App\Entity\File;
use App\Entity\Signalement;
use App\Entity\User;
use App\Messenger\Message\GenerateFileZipMessage;
use App\Security\Voter\SignalementVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/bo')]
class SignalementFileGenerateZipController extends AbstractController
{
    private const string TAB_DOCUMENT = '#documents';

    /**
     * @throws ExceptionInterface
     */
    #[Route('/signalements/{uuid:signalement}/zip', name: 'back_signalement_generate_zip')]
    public function index(
        Signalement $signalement,
        MessageBusInterface $messageBus,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_VIEW, $signalement);
        /** @var User $user */
        $user = $this->getUser();

        $generateFileZipMessage = new GenerateFileZipMessage(userId: $user->getId(), signalementId: $signalement->getId());
        $count = $signalement
            ->getFiles()
            ->filter(fn (File $file) => $file->isSituationImage())
            ->count();

        if (0 === $count) {
            $this->addFlash('error', 'Aucune photo existante pour ce signalement.');

            return $this->redirect(
                $this->generateUrl('back_signalement_view',
                    ['uuid' => $signalement->getUuid()]).self::TAB_DOCUMENT
            );
        }
        $messageBus->dispatch($generateFileZipMessage);

        $this->addFlash(
            'success',
            \sprintf(
                'Les photos ont bien été envoyées dans un dossier compressé par e-mail à l\'adresse suivante : %s.
                L\'envoi peut prendre plusieurs minutes. N\'oubliez pas de consulter vos courriers indésirables (spam) !',
                $user->getEmail()
            )
        );

        return $this->redirect(
            $this->generateUrl('back_signalement_view',
                ['uuid' => $signalement->getUuid()]).self::TAB_DOCUMENT
        );
    }

    /**
     * @throws ExceptionInterface
     */
    #[Route(
        '/signalements/{uuid:signalement}/zip-selection',
        name: 'back_signalement_generate_zip_selection',
        methods: ['POST']
    )]
    public function selection(
        Request $request,
        Signalement $signalement,
        #[MapRequestPayload(validationGroups: ['false'])] GenerateFileZipSelectionRequest $generateFileZipSelectionRequest,
        MessageBusInterface $messageBus,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted(SignalementVoter::SIGN_VIEW, $signalement);

        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('zip_selection_'.$signalement->getUuid(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }
        $violations = $validator->validate($generateFileZipSelectionRequest);
        if (\count($violations) > 0) {
            $this->addFlash('error', 'Aucune photo sélectionnée.');

            return $this->redirectToRoute('back_signalement_view', ['uuid' => $signalement->getUuid()]);
        }

        $messageBus->dispatch(new GenerateFileZipMessage(
            userId: $user->getId(),
            signalementId: $signalement->getId(),
            fileIds: $generateFileZipSelectionRequest->fileIds,
        ));

        $this->addFlash(
            'success',
            \sprintf(
                'Les photos ont bien été envoyées dans un dossier compressé par e-mail à l\'adresse suivante : %s.
                L\'envoi peut prendre plusieurs minutes. N\'oubliez pas de consulter vos courriers indésirables (spam) !',
                $user->getEmail()
            )
        );

        return $this->redirect(
            $this->generateUrl('back_signalement_view',
                ['uuid' => $signalement->getUuid()]).self::TAB_DOCUMENT
        );
    }
}
