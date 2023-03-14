<?php

namespace App\Controller\Back;

use App\Entity\Affectation;
use App\Entity\Criticite;
use App\Entity\Enum\MotifCloture;
use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\AffectationRepository;
use App\Repository\CritereRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\SearchFilterService;
use DateTime;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/signalements/', name: 'back_index')]
    public function index(
        EntityManagerInterface $em,
        CritereRepository $critereRepository,
        TerritoryRepository $territoryRepository,
        UserRepository $userRepository,
        SignalementRepository $signalementRepository,
        Request $request,
        AffectationRepository $affectationRepository,
        PartnerRepository $partnerRepository,
        SearchFilterService $searchFilterService,
        TagRepository $tagsRepository): Response
    {
        $title = 'Administration - Tableau de bord';
        $filters = $searchFilterService->setRequest($request)->setFilters()->getFilters();
        $countActiveFilters = $searchFilterService->getCountActive();
        /** @var User $user */
        $user = $this->getUser();
        $territory = $user->getTerritory(); // If user is not admin, he can only see his territory
        if (!$this->isGranted('ROLE_ADMIN_TERRITORY') || $filters['partners'] || $filters['affectations']) {
            $this->req = $affectationRepository->findByStatusAndOrCityForUser($user, $filters, $request->get('export'));
            if (!$request->get('export')) {
                $this->iterator = $this->req->getIterator()->getArrayCopy();
            }
            if (!$this->isGranted('ROLE_ADMIN_TERRITORY') && $user->getPartner()) {
                $counts = $affectationRepository->countByStatusForUser($user, $territory);
                $signalementsCount = [
                    Signalement::STATUS_NEED_VALIDATION => $counts[0] ?? ['count' => 0],
                    Signalement::STATUS_ACTIVE => $counts[1] ?? ['count' => 0],
                    Signalement::STATUS_CLOSED => ['count' => ($counts[3]['count'] ?? 0) + ($counts[2]['count'] ?? 0)],
                ];
                $signalementsCount['total'] = \count($this->req);
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
            $signalementsCount = $signalementRepository->countByStatus($territory);
        }
        $criteria = new Criteria();
        if ($this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $criteria->where(Criteria::expr()->neq('statut', 7));
            if ($territory) {
                $criteria->andWhere(Criteria::expr()->eq('territory', $territory));
            }
            $signalementsCount['total'] = $signalementRepository->matching($criteria)->count();
        }
        if ($user->isSuperAdmin()) {
            $users = [
                'active' => $userRepository->matching($criteria->where(Criteria::expr()->eq('statut', 1)))->count(),
                'inactive' => $userRepository->matching($criteria->where(Criteria::expr()->eq('statut', 0)))->count(),
            ];
        } else {
            $users = [
                'active' => $userRepository->matching($criteria->where(Criteria::expr()->eq('statut', 1))->andWhere(Criteria::expr()->eq('territory', $territory)))->count(),
                'inactive' => $userRepository->matching($criteria->where(Criteria::expr()->eq('statut', 0))->andWhere(Criteria::expr()->eq('territory', $territory)))->count(),
            ];
        }

        $signalements = [
            'list' => $this->req,
            'total' => \count($this->req),
            'page' => (int) $filters['page'],
            'pages' => (int) ceil(\count($this->req) / 30),
            'counts' => $signalementsCount ?? [],
            'csrfTokens' => $this->generateCsrfToken(),
        ];

        if ($request->get('pagination')) {
            return $this->stream('back/table_result.html.twig', ['filters' => $filters, 'signalements' => $signalements]);
        }
        $criteres = $critereRepository->findAllList();
        if ($request->get('export') && $this->isCsrfTokenValid('export_token_'.$user->getId(), $request->get('_token'))) {
            return $this->export($this->req, $em);
        }

        if ($territory) {
            $criteria->andWhere(Criteria::expr()->eq('territory', $territory));
        }

        $userToFilterCities = $user;
        if ($this->isGranted('ROLE_ADMIN') || $this->isGranted('ROLE_ADMIN_TERRITORY')) {
            $userToFilterCities = null;
        }

        return $this->render('back/index.html.twig', [
            'title' => $title,
            'filters' => $filters,
            'countActiveFilters' => $countActiveFilters,
            'displayRefreshAll' => true,
            'territories' => $territoryRepository->findAllList(),
            'cities' => $signalementRepository->findCities($userToFilterCities, $territory),
            'partners' => $partnerRepository->findAllList($territory),
            'signalements' => $signalements,
            'users' => $users,
            'criteres' => $criteres,
            'tags' => $tagsRepository->findAllActive($territory),
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
                } elseif ($signalement->$method() instanceof MotifCloture && null !== $signalement->$method()) {
                    $data[] = str_replace(';', '', $signalement->$method()->label());
                } elseif (!\is_array($signalement->$method()) && !($signalement->$method() instanceof ArrayCollection) && \is_string($signalement->$method())) {
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
