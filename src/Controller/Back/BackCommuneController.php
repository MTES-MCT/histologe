<?php

namespace App\Controller\Back;

use App\Entity\Commune;
use App\Form\CommuneType;
use App\Form\SearchCommuneType;
use App\Repository\AutoAffectationRuleRepository;
use App\Repository\CommuneRepository;
use App\Repository\PartnerRepository;
use App\Repository\SignalementRepository;
use App\Repository\TerritoryRepository;
use App\Service\FormHelper;
use App\Service\Gouv\Ban\AddressService;
use App\Service\ListFilters\SearchCommune;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bo/commune')]
#[IsGranted('ROLE_ADMIN')]
class BackCommuneController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'standard_max_list_pagination')]
        private readonly int $maxListPagination,
        private readonly CommuneRepository $communeRepository,
    ) {
    }

    /**
     * @return array{FormInterface, SearchCommune, Paginator<Commune>}
     */
    private function handleSearch(Request $request, bool $fromSearchParams = false): array
    {
        $searchCommune = new SearchCommune();
        $form = $this->createForm(SearchCommuneType::class, $searchCommune);
        FormHelper::handleFormSubmitFromRequestOrSearchParams($form, $request, $fromSearchParams);
        if ($form->isSubmitted() && !$form->isValid()) {
            $searchCommune = new SearchCommune();
        }
        /** @var Paginator<Commune> $paginatedCommunes */
        $paginatedCommunes = $this->communeRepository->findFilteredPaginated($searchCommune, $this->maxListPagination);

        return [$form, $searchCommune, $paginatedCommunes];
    }

    #[Route('/', name: 'back_commune_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        [$form, $searchCommune, $paginatedCommunes] = $this->handleSearch($request);

        return $this->render('back/commune/index.html.twig', [
            'form' => $form,
            'searchCommune' => $searchCommune,
            'communes' => $paginatedCommunes,
            'pages' => (int) ceil($paginatedCommunes->count() / $this->maxListPagination),
        ]);
    }

    #[Route('/editer/{commune}', name: 'back_commune_edit', methods: ['GET', 'POST'])]
    public function edit(
        Commune $commune,
        Request $request,
        SignalementRepository $signalementRepository,
        AutoAffectationRuleRepository $autoAffectationRuleRepository,
        PartnerRepository $partnerRepository,
        TerritoryRepository $territoryRepository,
        EntityManagerInterface $em,
        AddressService $addressService,
    ): Response {
        $poiCommune = $addressService->getMunicipalityByCityCode($commune->getNom(), $commune->getCodeInsee());
        $countSignalementsWithCommune = $signalementRepository->countForCommune($commune);
        $inconsistentSignalements = $signalementRepository->findWithInconsistentCommuneName($commune);
        $form = $this->createForm(CommuneType::class, $commune);
        $originalNom = $commune->getNom();
        $originalInsee = $commune->getCodeInsee();
        $signalementsWithInsee = $signalementRepository->findBy(['inseeOccupant' => $originalInsee]);
        $countSignalementsWithInsee = \count($signalementsWithInsee);
        $autoAffectationRulesWithInseeToInclude = $autoAffectationRuleRepository->findWithInseeToInclude($originalInsee);
        $countWithAutoAffectationRulesWithInseeToInclude = \count($autoAffectationRulesWithInseeToInclude);
        $autoAffectationRulesWithInseeToExclude = $autoAffectationRuleRepository->findWithInseeToExclude($originalInsee);
        $countWithAutoAffectationRulesWithInseeToExclude = \count($autoAffectationRulesWithInseeToExclude);
        $partenairesWithInsee = $partnerRepository->findWithInsee($originalInsee);
        $countWithPartenairesWithInsee = \count($partenairesWithInsee);
        $territoriesWithInsee = $territoryRepository->findWithAuthorizedCodesInsee($originalInsee);
        $countWithTerritoriesWithInsee = \count($territoriesWithInsee);
        $form->handleRequest($request);
        $nomUpdated = false;
        $inseeUpdated = false;
        if ($form->isSubmitted() && $form->isValid()) {
            // Avant des propager les modifications, on s'assure que la correspondance dans la BAN existe
            $newPoiCommune = $addressService->getMunicipalityByCityCode($commune->getNom(), $commune->getCodeInsee());
            // Browse commune names and city codes from BAN response to find an exact match with the commune name and code INSEE
            $hasCorrespondingCommuneName = !empty($newPoiCommune) ? in_array($commune->getNom(), $newPoiCommune->getNames(), true) : false;
            $hasCorrespondingInseeCode = !empty($newPoiCommune) ? in_array($commune->getCodeInsee(), $newPoiCommune->getCityCodes(), true) : false;
            if (!$hasCorrespondingCommuneName || !$hasCorrespondingInseeCode) {
                $this->addFlash('error', ['title' => 'Erreur de validation', 'message' => 'La correspondance entre le nom et le code INSEE de la commune n\'existe pas dans la Base Adresse Nationale. Veuillez vérifier les informations saisies.']);

                return $this->redirectToRoute('back_commune_edit', ['commune' => $commune->getId()]);
            }

            // Modification du nom de la commune
            if ($originalNom !== $commune->getNom()) {
                $nomUpdated = true;
                foreach ($inconsistentSignalements as $signalement) {
                    $signalement->setVilleOccupant($commune->getNom());
                }
            }
            // Modification du code INSEE de la commune, et propagation aux données associées
            if ($originalInsee !== $commune->getCodeInsee()) {
                $inseeUpdated = true;
                // Si une commune avec le nouveau code INSEE existe, on met à jour les signalements pour qu'ils pointent vers cette commune
                foreach ($signalementsWithInsee as $signalement) {
                    $signalement->setInseeOccupant($commune->getCodeInsee());
                }
                // auto-affectation : change in list of inseeToInclude (type string)
                foreach ($autoAffectationRulesWithInseeToInclude as $autoAffectationRule) {
                    $inseeToInclude = explode(',', $autoAffectationRule->getInseeToInclude());
                    $inseeToInclude = array_diff($inseeToInclude, [$originalInsee]);
                    $inseeToInclude[] = $commune->getCodeInsee();
                    $autoAffectationRule->setInseeToInclude(implode(',', $inseeToInclude));
                }
                // auto-affectation : change in list of inseeToExclude (type array)
                foreach ($autoAffectationRulesWithInseeToExclude as $autoAffectationRule) {
                    $inseeToExclude = $autoAffectationRule->getInseeToExclude();
                    $inseeToExclude = array_diff($inseeToExclude, [$originalInsee]);
                    $inseeToExclude[] = $commune->getCodeInsee();
                    $autoAffectationRule->setInseeToExclude(array_values($inseeToExclude));
                }
                // partenaires (type array)
                foreach ($partenairesWithInsee as $partenaire) {
                    $inseeList = $partenaire->getInsee();
                    $inseeList = array_diff($inseeList, [$originalInsee]);
                    $inseeList[] = $commune->getCodeInsee();
                    $partenaire->setInsee(array_values($inseeList));
                }
                // territories (type array)
                foreach ($territoriesWithInsee as $territory) {
                    $authorizedInseeList = $territory->getAuthorizedCodesInsee();
                    $authorizedInseeList = array_diff($authorizedInseeList, [$originalInsee]);
                    $authorizedInseeList[] = $commune->getCodeInsee();
                    $territory->setAuthorizedCodesInsee(array_values($authorizedInseeList));
                }
            }

            $em->flush();
            $message = 'La commune a bien été modifiée.';
            if ($nomUpdated && count($inconsistentSignalements) > 0) {
                $message .= sprintf(' %d signalement(s) ont été mis à jour pour être cohérents avec le nouveau nom de la commune.', count($inconsistentSignalements));
            }
            if ($inseeUpdated) {
                if ($countSignalementsWithInsee > 0) {
                    $message .= sprintf(' %d signalement(s) ont été mis à jour pour être cohérents avec le nouveau code INSEE de la commune.', $countSignalementsWithInsee);
                }
                if ($countWithAutoAffectationRulesWithInseeToInclude > 0) {
                    $message .= sprintf(' %d règle(s) d\'auto-affectation ont été mises à jour pour inclure le nouveau code INSEE de la commune.', $countWithAutoAffectationRulesWithInseeToInclude);
                }
                if ($countWithAutoAffectationRulesWithInseeToExclude > 0) {
                    $message .= sprintf(' %d règle(s) d\'auto-affectation ont été mises à jour pour exclure le nouveau code INSEE de la commune.', $countWithAutoAffectationRulesWithInseeToExclude);
                }
                if ($countWithPartenairesWithInsee > 0) {
                    $message .= sprintf(' %d partenaire(s) ont été mis à jour pour être cohérents avec le nouveau code INSEE de la commune.', $countWithPartenairesWithInsee);
                }
                if ($countWithTerritoriesWithInsee > 0) {
                    $message .= sprintf(' %d territoire(s) ont été mis à jour pour être cohérents avec le nouveau code INSEE de la commune.', $countWithTerritoriesWithInsee);
                }
            }
            $this->addFlash('success', ['title' => 'Modifications enregistrées', 'message' => $message]);

            return $this->redirectToRoute('back_commune_edit', ['commune' => $commune->getId()]);
        }

        return $this->render('back/commune/edit.html.twig', [
            'form' => $form,
            'commune' => $commune,
            'countSignalementsWithCommune' => $countSignalementsWithCommune,
            'inconsistentSignalements' => $inconsistentSignalements,
            'countSignalementsWithInsee' => $countSignalementsWithInsee,
            'countWithAutoAffectationRulesWithInseeToInclude' => $countWithAutoAffectationRulesWithInseeToInclude,
            'countWithAutoAffectationRulesWithInseeToExclude' => $countWithAutoAffectationRulesWithInseeToExclude,
            'countWithPartenairesWithInsee' => $countWithPartenairesWithInsee,
            'countWithTerritoriesWithInsee' => $countWithTerritoriesWithInsee,
            'poiCommune' => $poiCommune,
        ]);
    }
}
