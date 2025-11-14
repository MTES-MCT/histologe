<?php

namespace App\Controller\Back;

use App\Entity\Enum\SignalementStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Form\SearchSignalementInjonctionType;
use App\Repository\SignalementRepository;
use App\Security\Voter\UserVoter;
use App\Service\ListFilters\SearchSignalementInjonction;
use Dompdf\Dompdf;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/signalement-injonction')]
class SignalementInjonctionController extends AbstractController
{
    #[Route('/', name: 'back_injonction_signalement_index', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        SignalementRepository $signalementRepository,
        #[Autowire(param: 'standard_max_list_pagination')] int $maxListPagination,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();
        $this->denyAccessUnlessGranted(UserVoter::SEE_INJONCTION_BAILLEUR, $user);

        $searchSignalementInjonction = new SearchSignalementInjonction($user);
        $form = $this->createForm(SearchSignalementInjonctionType::class, $searchSignalementInjonction);
        $form->handleRequest($request);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchSignalementInjonction = new SearchSignalementInjonction($user);
        }
        $userPartners = (!$user->isSuperAdmin() && !$user->isTerritoryAdmin()) ? $user->getPartners() : false;
        $paginatedSignalementInjonction = $signalementRepository->findInjonctionFilteredPaginated($searchSignalementInjonction, $maxListPagination, $userPartners);

        return $this->render('back/signalement-injonction/index.html.twig', [
            'form' => $form,
            'searchSignalement' => $searchSignalementInjonction,
            'signalements' => $paginatedSignalementInjonction,
            'pages' => (int) ceil($paginatedSignalementInjonction->count() / $maxListPagination),
        ]);
    }

    #[Route('/{uuid:signalement}/courrier-bailleur', name: 'back_injonction_signalement_courrier_bailleur', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function courrierBailleur(
        Signalement $signalement,
    ): Response {
        if (SignalementStatus::INJONCTION_BAILLEUR !== $signalement->getStatut()) {
            throw $this->createAccessDeniedException();
        }
        $writer = new PngWriter();

        $url = $this->generateUrl('app_login_bailleur', [
            'bailleur_reference' => $signalement->getReference(),
            'bailleur_code' => $signalement->getLoginBailleur(),
        ], referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
        $qrCode = new QrCode(data: $url);

        $result = $writer->write($qrCode);
        $content = $this->renderView('back/signalement-injonction/courrier-bailleur.html.twig', ['signalement' => $signalement, 'qrCode' => $result->getDataUri()]);

        $domPdf = new Dompdf();
        $domPdf->loadHtml($content);
        $domPdf->render();

        $response = new Response($domPdf->output());
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'inline; filename="courrier-bailleur.pdf"');

        return $response;
    }
}
