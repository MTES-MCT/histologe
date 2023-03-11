<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Criticite;
use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\AffectationRepository;
use App\Repository\SignalementRepository;
use App\Service\SearchFilterService;
use App\Service\Signalement\SearchFilterOptionDataProvider;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\QueryException;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/bo')]
class BackController extends AbstractController
{
    private $req;
    private $iterator;

    public function __construct(private CsrfTokenManagerInterface $csrfTokenManager)
    {
    }

    /**
     * @throws QueryException
     * @throws InvalidArgumentException
     */
    #[Route('/signalements/', name: 'back_index')]
    public function index(
        EntityManagerInterface $em,
        SignalementRepository $signalementRepository,
        Request $request,
        AffectationRepository $affectationRepository,
        SearchFilterService $searchFilterService,
        SearchFilterOptionDataProvider $searchFilterOptionDataProvider
    ): Response {
        $title = 'Administration - Tableau de bord';
        $filters = $searchFilterService->setRequest($request)->setFilters()->getFilters();
        $countActiveFilters = $searchFilterService->getCountActive();
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN_TERRITORY') || $filters['partners'] || $filters['affectations']) {
            $this->req = $affectationRepository->findByStatusAndOrCityForUser($user, $filters, $request->get('export'));
            if (!$request->get('export')) {
                $this->iterator = $this->req->getIterator()->getArrayCopy();
            }
            if (!$this->isGranted('ROLE_ADMIN_TERRITORY') && $user->getPartner()) {
                $status = [
                    Affectation::STATUS_WAIT => Signalement::STATUS_NEED_VALIDATION,
                    Affectation::STATUS_ACCEPTED => Signalement::STATUS_ACTIVE,
                    Affectation::STATUS_CLOSED => Signalement::STATUS_CLOSED,
                    Affectation::STATUS_REFUSED => Signalement::STATUS_CLOSED,
                ];
                if ($this->iterator) {
                    foreach ($this->iterator as $item) {
                        $item->getSignalement()->setStatut((int) $status[$item->getStatut()]);
                    }
                }
            }
        } else {
            $filters['authorized_codes_insee'] = $this->getParameter('authorized_codes_insee');
            $this->req = $signalementRepository->findByStatusAndOrCityForUser($user, $filters, $request->get('export'));
        }

        $signalements = [
            'list' => $this->req,
            'total' => \count($this->req),
            'page' => (int) $filters['page'],
            'pages' => (int) ceil(\count($this->req) / 30),
            'csrfTokens' => $this->generateCsrfToken(),
        ];

        if ($request->get('pagination')) {
            return $this->stream('back/table_result.html.twig', ['filters' => $filters, 'signalements' => $signalements]);
        }

        if ($request->get('export') && $this->isCsrfTokenValid('export_token_'.$user->getId(), $request->get('_token'))) {
            return $this->export($this->req, $em);
        }

        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filters' => $filters,
            'filtersOptionData' => $searchFilterOptionDataProvider->getData($user),
            'countActiveFilters' => $countActiveFilters,
            'displayRefreshAll' => true,
            'signalements' => $signalements,
        ]);
    }

    private function export(array $signalements, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('SIGN_EXPORT', new ArrayCollection($signalements));
        $tmpFileName = (new Filesystem())->tempnam(sys_get_temp_dir(), 'sb_');
        $tmpFile = fopen($tmpFileName, 'wb+');
        $headers = $em->getClassMetadata(Signalement::class)->getFieldNames();
        fputcsv($tmpFile, array_merge($headers, ['situations', 'criteres']), ';');
        foreach ($signalements as $signalement) {
            if ($signalement instanceof Affectation) {
                $signalement = $signalement->getSignalement();
            }
            $data = [];
            foreach ($headers as $header) {
                $method = 'get'.ucfirst($header);
                if ('documents' === $header || 'photos' === $header) {
                    $items = $signalement->$method();
                    if (!$items) {
                        $data[] = 'SANS';
                    } else {
                        $arr = [];
                        foreach ($items as $item) {
                            $arr[] = $item['titre'] ?? $item['file'] ?? $item;
                        }
                        $data[] = implode(",\r\n", $arr);
                    }
                } elseif ('statut' === $header) {
                    $statut = match ($signalement->$method()) {
                        Signalement::STATUS_NEED_VALIDATION => 'A VALIDER',
                        Signalement::STATUS_ACTIVE => 'EN COURS',
                        Signalement::STATUS_CLOSED => 'CLOS',
                        Signalement::STATUS_REFUSED => 'REFUSE',
                        default => $signalement->$method(),
                    };
                    $data[] = $statut;
                } elseif ('geoloc' === $header && !empty($signalement->$method()['lat']) && !empty($signalement->$method()['lng'])) {
                    $data[] = 'LAT: '.$signalement->$method()['lat'].' LNG: '.$signalement->$method()['lng'];
                } elseif ($signalement->$method() instanceof DateTimeImmutable || $signalement->$method() instanceof DateTime) {
                    $data[] = $signalement->$method()->format('d.m.Y');
                } elseif (\is_bool($signalement->$method())) {
                    $data[] = $signalement->$method() ? 'OUI' : 'NON';
                } elseif (!\is_array($signalement->$method()) && !($signalement->$method() instanceof ArrayCollection)) {
                    $data[] = str_replace(';', '', $signalement->$method());
                } elseif ('' == $signalement->$method()) {
                    $data[] = 'N/R';
                } else {
                    $data[] = '[]';
                }
            }
            $situations = $criteres = new ArrayCollection();
            $signalement->getCriticites()->filter(function (Criticite $criticite) use ($situations, $criteres) {
                $labels = ['DANGER', 'MOYEN', 'GRAVE', 'TRES GRAVE'];
                $critere = $criticite->getCritere();
                $situation = $criticite->getCritere()->getSituation();
                $critereAndCriticite = $critere->getLabel().' ('.$labels[$criticite->getScore()].')';
                if (!$situations->contains($situation->getLabel())) {
                    $situations->add($situation->getLabel());
                }
                if (!$criteres->contains($critereAndCriticite)) {
                    $situations->add($critereAndCriticite);
                }
            });
            $data[] = implode(",\r\n", $situations->toArray());
            $data[] = implode(",\r\n", $criteres->toArray());
            fputcsv($tmpFile, $data, ';');
        }
        fclose($tmpFile);
        $response = $this->file($tmpFileName, 'dynamic-csv-file.csv');
        $response->headers->set('Content-type', 'application/csv');

        return $response;
    }

    /**
     * Generate csrf token in side server for ajav request
     * Fix Twig\Error\RuntimeError in order to do not generate csrf token (session) after the headers have been sent.
     */
    private function generateCsrfToken(): array
    {
        $csrfTokens = [];
        foreach ($this->req as $item) {
            /** @var Signalement $signalement */
            $signalement = $item;
            if ($item instanceof Affectation) {
                $signalement = $item->getSignalement();
            }

            if ($signalement instanceof Signalement) {
                $csrfTokens[$signalement->getUuid()] = $this->csrfTokenManager->getToken(
                    'signalement_delete_'.$signalement->getId()
                )->getValue();
            }
        }

        return $csrfTokens;
    }
}
