<?php

namespace App\Controller\Back;

use App\Entity\Note;
use App\Entity\Signalement;
use App\Entity\TagUser;
use App\Entity\User;
use App\Repository\NoteRepository;
use App\Repository\TagUserRepository;
use App\Service\MessageHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalements')]
#[IsGranted('ROLE_ADMIN')]
class SignalementNoteController extends AbstractController
{
    #[Route('/{uuid:signalement}/save-tag-users', name: 'back_signalement_save_tag_users', methods: 'POST')]
    public function saveSignalementTagUsers(
        Signalement $signalement,
        Request $request,
        TagUserRepository $tagUserRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('signalement_save_tag_users', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var User $user */
        $user = $this->getUser();
        $tagIds = (string) $request->request->get('tag-ids');
        $tagList = array_filter(explode(',', $tagIds));
        foreach ($signalement->getTagUsers() as $existingTag) {
            if ($existingTag->getUser() === $user && !\in_array((string) $existingTag->getId(), $tagList, true)) {
                $signalement->removeTagUser($existingTag);
            }
        }
        foreach ($tagList as $tagId) {
            $tagUser = $tagUserRepository->findOneBy(['id' => $tagId, 'user' => $user]);
            if ($tagUser) {
                $signalement->addTagUser($tagUser);
            }
        }
        $entityManager->flush();

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Vos étiquettes ont bien été modifiées.'];

        return $this->json($this->buildTagUsersResponse($signalement, $flashMessages));
    }

    #[Route('/{uuid:signalement}/create-tag-user', name: 'back_signalement_create_tag_user', methods: 'POST')]
    public function createSignalementTagUser(
        Signalement $signalement,
        Request $request,
        TagUserRepository $tagUserRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('signalement_create_tag_user', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var User $user */
        $user = $this->getUser();
        $label = trim((string) $request->request->get('label'));
        if ('' === $label) {
            return $this->json(
                ['errors' => ['label' => ['errors' => ['Merci de saisir un nom pour l\'étiquette.']]]],
                Response::HTTP_BAD_REQUEST
            );
        }
        $tagUser = $tagUserRepository->findOneBy(['label' => $label, 'user' => $user]);
        if ($tagUser) {
            return $this->json(
                ['errors' => ['label' => ['errors' => ['Une étiquette portant ce nom existe déjà.']]]],
                Response::HTTP_BAD_REQUEST
            );
        }
        $tagUser = (new TagUser())->setLabel($label);
        $user->addTagUser($tagUser);
        $entityManager->persist($tagUser);
        $signalement->addTagUser($tagUser);
        $entityManager->flush();

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'L\'étiquette a bien été créée et attribuée.'];

        return $this->json($this->buildTagUsersResponse($signalement, $flashMessages));
    }

    #[Route('/{uuid:signalement}/save-note', name: 'back_signalement_save_note', methods: 'POST')]
    public function saveSignalementNote(
        Signalement $signalement,
        Request $request,
        NoteRepository $noteRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('signalement_save_note', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var User $user */
        $user = $this->getUser();
        $content = trim((string) $request->request->get('content'));
        $note = $noteRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        if ('' === $content) {
            if ($note) {
                $entityManager->remove($note);
                $entityManager->flush();
            }
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Votre note a bien été supprimée.'];

            return $this->json($this->buildNoteResponse($signalement, null, $flashMessages));
        }
        if (!$note) {
            $note = (new Note())->setUser($user)->setSignalement($signalement);
            $entityManager->persist($note);
        }
        $note->setContent($content);
        $entityManager->flush();

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Votre note a bien été enregistrée.'];

        return $this->json($this->buildNoteResponse($signalement, $note, $flashMessages));
    }

    #[Route('/{uuid:signalement}/delete-note', name: 'back_signalement_delete_note', methods: 'POST')]
    public function deleteSignalementNote(
        Signalement $signalement,
        Request $request,
        NoteRepository $noteRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('signalement_delete_note', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var User $user */
        $user = $this->getUser();
        $note = $noteRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        if ($note) {
            $entityManager->remove($note);
            $entityManager->flush();
        }

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Votre note a bien été supprimée.'];

        return $this->json($this->buildNoteResponse($signalement, null, $flashMessages));
    }

    /**
     * @param array<int, array<string, string>> $flashMessages
     *
     * @return array<string, mixed>
     */
    private function buildTagUsersResponse(Signalement $signalement, array $flashMessages): array
    {
        return [
            'stayOnPage' => true,
            'flashMessages' => $flashMessages,
            'htmlTargetContents' => [
                [
                    'target' => '#signalement-tag-users-container',
                    'content' => $this->renderView('back/signalement/view/tabs/_tab-notes-tags.html.twig', ['signalement' => $signalement]),
                ],
            ],
            'functions' => [
                ['name' => 'initSearchAndSelectBadges'],
                ['name' => 'attachAjaxFormHandlers'],
            ],
        ];
    }

    /**
     * @param array<int, array<string, string>> $flashMessages
     *
     * @return array<string, mixed>
     */
    private function buildNoteResponse(Signalement $signalement, ?Note $note, array $flashMessages): array
    {
        return [
            'stayOnPage' => true,
            'flashMessages' => $flashMessages,
            'htmlTargetContents' => [
                [
                    'target' => '#signalement-note-container',
                    'content' => $this->renderView('back/signalement/view/tabs/_tab-notes-note.html.twig', [
                        'signalement' => $signalement,
                        'userNote' => $note,
                    ]),
                ],
            ],
            'functions' => [
                ['name' => 'attachAjaxFormHandlers'],
                ['name' => 'reloadNoteEditor'],
            ],
        ];
    }
}
