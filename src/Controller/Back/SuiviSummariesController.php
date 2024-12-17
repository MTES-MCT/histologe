<?php

namespace App\Controller\Back;

use App\Entity\Territory;
use App\Entity\User;
use App\Form\SuiviSummariesType;
use App\Messenger\Message\SuiviSummariesMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/resumes-suivi')]
class SuiviSummariesController extends AbstractController
{
    /**
     * @throws ExceptionInterface
     */
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
            /** @var Territory $territory */
            $territory = $form->get('territory')->getData();
            $count = $form->get('count')->getData();
            $prompt = $form->get('prompt')->getData();
            $promptRole = $form->get('promptRole')->getData();
            $temperature = $form->get('temperature')->getData();
            $model = $form->get('model')->getData();
            $querySignalement = $form->get('querySignalement')->getData();

            $messageBus->dispatch(new SuiviSummariesMessage(
                $user->getId(),
                $territory->getId(),
                $count,
                $prompt,
                $promptRole,
                $temperature,
                $model,
                $querySignalement)
            );
            $this->addFlash(
                'success',
                \sprintf(
                    'L\'export vous sera envoyé par e-mail à l\'adresse suivante : %s. Il arrivera d\'ici quelques minutes. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                    $user->getEmail()
                )
            );
        }

        return $this->render('back/resumes-suivi/index.html.twig', [
            'form' => $form,
        ]);
    }
}
