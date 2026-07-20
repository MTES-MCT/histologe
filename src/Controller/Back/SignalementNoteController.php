<?php

namespace App\Controller\Back;

use App\Entity\PersonalNote;
use App\Entity\PersonalTag;
use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\PersonalNoteRepository;
use App\Repository\PersonalTagRepository;
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
    #[Route('/{uuid:signalement}/save-personal-tags', name: 'back_signalement_save_personal_tags', methods: 'POST')]
    public function saveSignalementPersonalTags(
        Signalement $signalement,
        Request $request,
        PersonalTagRepository $personalTagRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('signalement_save_personal_tags', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var User $user */
        $user = $this->getUser();
        $tagIds = (string) $request->request->get('tag-ids');
        $tagList = array_filter(explode(',', $tagIds));
        foreach ($signalement->getPersonalTags() as $existingTag) {
            if ($existingTag->getUser() === $user && !\in_array((string) $existingTag->getId(), $tagList, true)) {
                $signalement->removePersonalTag($existingTag);
            }
        }
        foreach ($tagList as $tagId) {
            $personalTag = $personalTagRepository->findOneBy(['id' => $tagId, 'user' => $user]);
            if ($personalTag) {
                $signalement->addPersonalTag($personalTag);
            }
        }
        $entityManager->flush();

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Vos étiquettes ont bien été modifiées.'];

        return $this->json($this->buildPersonalTagsResponse($signalement, $flashMessages));
    }

    #[Route('/{uuid:signalement}/create-personal-tag', name: 'back_signalement_create_personal_tag', methods: 'POST')]
    public function createSignalementPersonalTag(
        Signalement $signalement,
        Request $request,
        PersonalTagRepository $personalTagRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('signalement_create_personal_tag', (string) $request->request->get('_token'))) {
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
        $personalTag = $personalTagRepository->findOneBy(['label' => $label, 'user' => $user]);
        if ($personalTag) {
            return $this->json(
                ['errors' => ['label' => ['errors' => ['Une étiquette portant ce nom existe déjà.']]]],
                Response::HTTP_BAD_REQUEST
            );
        }
        $personalTag = (new PersonalTag())->setLabel($label);
        $user->addPersonalTag($personalTag);
        $entityManager->persist($personalTag);
        $signalement->addPersonalTag($personalTag);
        $entityManager->flush();

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'L\'étiquette a bien été créée et attribuée.'];

        return $this->json($this->buildPersonalTagsResponse($signalement, $flashMessages));
    }

    #[Route('/{uuid:signalement}/save-personal-note', name: 'back_signalement_save_personal_note', methods: 'POST')]
    public function saveSignalementPersonalNote(
        Signalement $signalement,
        Request $request,
        PersonalNoteRepository $personalNoteRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('signalement_save_personal_note', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var User $user */
        $user = $this->getUser();
        $content = trim((string) $request->request->get('content'));
        $personalNote = $personalNoteRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        if ('' === $content) {
            if ($personalNote) {
                $entityManager->remove($personalNote);
                $entityManager->flush();
            }
            $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Votre note a bien été supprimée.'];

            return $this->json($this->buildPersonalNoteResponse($signalement, null, $flashMessages));
        }
        if (!$personalNote) {
            $personalNote = (new PersonalNote())->setUser($user)->setSignalement($signalement);
            $entityManager->persist($personalNote);
        }
        $personalNote->setContent($content);
        $entityManager->flush();

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Votre note a bien été enregistrée.'];

        return $this->json($this->buildPersonalNoteResponse($signalement, $personalNote, $flashMessages));
    }

    #[Route('/{uuid:signalement}/delete-personal-note', name: 'back_signalement_delete_personal_note', methods: 'POST')]
    public function deleteSignalementPersonalNote(
        Signalement $signalement,
        Request $request,
        PersonalNoteRepository $personalNoteRepository,
        EntityManagerInterface $entityManager,
    ): JsonResponse {
        if (!$this->isCsrfTokenValid('signalement_delete_personal_note', (string) $request->request->get('_token'))) {
            $flashMessages[] = ['type' => 'alert', 'title' => 'Erreur', 'message' => MessageHelper::ERROR_MESSAGE_CSRF];

            return $this->json(['stayOnPage' => true, 'flashMessages' => $flashMessages]);
        }
        /** @var User $user */
        $user = $this->getUser();
        $personalNote = $personalNoteRepository->findOneBy(['user' => $user, 'signalement' => $signalement]);
        if ($personalNote) {
            $entityManager->remove($personalNote);
            $entityManager->flush();
        }

        $flashMessages[] = ['type' => 'success', 'title' => 'Modifications enregistrées', 'message' => 'Votre note a bien été supprimée.'];

        return $this->json($this->buildPersonalNoteResponse($signalement, null, $flashMessages));
    }

    /**
     * @param array<int, array<string, string>> $flashMessages
     *
     * @return array<string, mixed>
     */
    private function buildPersonalTagsResponse(Signalement $signalement, array $flashMessages): array
    {
        return [
            'stayOnPage' => true,
            'flashMessages' => $flashMessages,
            'htmlTargetContents' => [
                [
                    'target' => '#signalement-personal-tags-container',
                    'content' => $this->renderView('back/signalement/view/tabs/_tab-notes-personal-tags.html.twig', ['signalement' => $signalement]),
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
    private function buildPersonalNoteResponse(Signalement $signalement, ?PersonalNote $personalNote, array $flashMessages): array
    {
        return [
            'stayOnPage' => true,
            'flashMessages' => $flashMessages,
            'htmlTargetContents' => [
                [
                    'target' => '#signalement-personal-note-container',
                    'content' => $this->renderView('back/signalement/view/tabs/_tab-notes-personal-note.html.twig', [
                        'signalement' => $signalement,
                        'personalNote' => $personalNote,
                    ]),
                ],
            ],
            'functions' => [
                ['name' => 'attachAjaxFormHandlers'],
                ['name' => 'reloadPersonalNoteEditor'],
            ],
        ];
    }
}
