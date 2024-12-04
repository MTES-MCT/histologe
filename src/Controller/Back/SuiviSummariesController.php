<?php

namespace App\Controller\Back;

use App\Entity\User;
use App\Form\SuiviSummariesType;
use App\Messenger\Message\SuiviSummariesMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/resumes-suivi')]
class SuiviSummariesController extends AbstractController
{
    #[Route('/', name: 'back_suivi_summaries_index', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function index(
        Request $request,
        MessageBusInterface $messageBus,
    ): Response {
        $form = $this->createForm(SuiviSummariesType::class, null);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $this->getUser();
            $territory = $form->get('territory')->getData();
            $count = $form->get('count')->getData();
            $prompt = $form->get('prompt')->getData();
            $querySignalement = $form->get('querySignalement')->getData();

            $messageBus->dispatch(new SuiviSummariesMessage($user, $territory, $count, $prompt, $querySignalement));
            $this->addFlash(
                'success',
                \sprintf(
                    'L\'export vous sera envoyé par e-mail à l\'adresse suivante : %s. Il arrivera d\'ici quelques minutes. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                    $user->getEmail()
                )
            );

            return $this->redirectToRoute('back_suivi_summaries_index');
        }

        $this->displayErrors($form);

        return $this->render('back/resumes-suivi/index.html.twig', [
            'form' => $form,
        ]);
    }

    private function displayErrors(FormInterface $form): void
    {
        /** @var FormError $error */
        foreach ($form->getErrors(true) as $error) {
            $this->addFlash('error', $error->getMessage());
        }
    }
}
