<?php

namespace App\Controller\Back;

use App\Form\SearchUserType;
use App\Messenger\Message\UserExportMessage;
use App\Repository\UserRepository;
use App\Service\SearchUser;
use App\Service\UserExportLoader;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/utilisateurs')]
#[IsGranted('ROLE_ADMIN_TERRITORY')]
class BackUserController extends AbstractController
{
    public const MAX_LIST_PAGINATION = 25;

    public function __construct(
        #[Autowire(env: 'FEATURE_EXPORT_USERS')]
        bool $featureExportUsers,
    ) {
        if (!$featureExportUsers) {
            throw $this->createNotFoundException();
        }
    }

    #[Route('/', name: 'back_user_index', methods: ['GET'])]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        [$form, $searchUser, $paginatedUsers] = $this->handleSearchUser($request, $userRepository);

        return $this->render('back/user/index.html.twig', [
            'form' => $form,
            'searchUser' => $searchUser,
            'users' => $paginatedUsers,
            'pages' => (int) ceil($paginatedUsers->count() / self::MAX_LIST_PAGINATION),
        ]);
    }

    #[Route('/export', name: 'back_user_export', methods: ['GET', 'POST'])]
    public function export(Request $request, UserRepository $userRepository, MessageBusInterface $messageBus, ParameterBagInterface $parameterBag): Response
    {
        $originalMethod = $request->getMethod();
        $request->setMethod('GET'); // to prevent Symfony ignoring GET data while handlning the form
        [$form, $searchUser, $paginatedUsers] = $this->handleSearchUser($request, $userRepository);
        if ('POST' === $originalMethod) {
            $format = $request->request->get('file-format');
            if (!in_array($format, ['csv', 'xlsx'])) {
                $this->addFlash('error', 'Merci de sélectionner le format de l\'export.');

                return $this->redirectToRoute('back_user_export', $searchUser->getUrlParams());
            }
            $messageBus->dispatch(new UserExportMessage($searchUser, $format));
            $this->addFlash(
                'success',
                \sprintf(
                    'L\'export vous sera envoyé par e-mail à l\'adresse suivante : %s. Il arrivera d\'ici quelques minutes. N\'oubliez pas de regarder vos courriers indésirables (spam) !',
                    $searchUser->getUser()->getEmail()
                )
            );

            return $this->redirectToRoute('back_user_index', $searchUser->getUrlParams());
        }

        return $this->render('back/user/export.html.twig', [
            'searchUser' => $searchUser,
            'nbResults' => \count($paginatedUsers),
            'columns' => UserExportLoader::getColumnForUser($this->getUser(), $parameterBag->get('feature_permission_affectation')),
        ]);
    }

    private function handleSearchUser(Request $request, UserRepository $userRepository): array
    {
        $searchUser = new SearchUser($this->getUser());
        $form = $this->createForm(SearchUserType::class, $searchUser);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchUser = new SearchUser($this->getUser());
        }
        $paginatedUsers = $userRepository->findFilteredPaginated($searchUser, self::MAX_LIST_PAGINATION);

        return [$form, $searchUser, $paginatedUsers];
    }
}
