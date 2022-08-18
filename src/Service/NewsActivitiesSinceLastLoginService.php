<?php

namespace App\Service;

use App\Entity\Affectation;
use App\Entity\Signalement;
use App\Entity\SignalementUserAffectation;
use App\Entity\Suivi;
use App\Repository\AffectationRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\RequestStack;

class NewsActivitiesSinceLastLoginService
{
    private array $activities;

    public function __construct(
        private RequestStack $requestStack,
        private AffectationRepository $affectationRepo
    ) {}


    public function getAffectationsAndSuivis($user)
    {
        $lastActivity = $this->requestStack->getSession()->get('lastActionTime');
        $results = $this->affectationRepo->findByPartenaire($user->getPartner());
        $affectations = new ArrayCollection();
        $suivis = new ArrayCollection();
        $results->filter(function (Affectation $affectation) use ($affectations, $lastActivity) {
            $signalement = $affectation->getSignalement();
            if (!$affectations->contains($signalement) && $affectation->getStatut() === Affectation::STATUS_CLOSED)
                $affectations->add($signalement);
            $signalement->getSuivis()->filter(function (Suivi $suivi) use ($lastActivity) {
                return $suivi->getCreatedAt() > $lastActivity;
            });
        });
        return ['affectations' => $affectations, 'suivis' => $suivis];
    }


    public function set($user)
    {
//        $this->requestStack->getSession()->remove('lastActionTime');
//        $suivis = $this->getNewsSuivis($user);
//        dd($this->getAffectationsAndSuivis($user));
        $lastActiviy = $this->requestStack->getSession()->get('lastActionTime');
        $newsActivitiesSinceLastLogin = $this->requestStack->getSession()->get('_newsActivitiesSinceLastLogin') ?? new ArrayCollection();
        $activities = [];
        $newsActivitiesSinceLastLogin->filter(function (Affectation $affectation) use ($activities, $lastActiviy) {
            $affectation->getSignalement()->getSuivis()->filter(function (Suivi $suivi) use ($activities, $lastActiviy) {
                if ($suivi->getCreatedAt() > $lastActiviy)
                    $activities[] = $suivi;
            });
        });
        return $activities;

    }

    public function count(): int
    {
        if ($this->getAll())
            return count($this->getAll());
        return 0;
    }

    public function getAll(): bool|ArrayCollection|null
    {
        return $this->requestStack->getSession()->get('_newsActivitiesSinceLastLogin');
    }

    public function update(Signalement $signalement): ArrayCollection|bool|null
    {
        $activities = $this->requestStack->getSession()->get('lastActionTime');
        $activities[$signalement->getId()] = new DateTimeImmutable();
        $this->requestStack->getSession()->set('lastActionTime', $activities);
        $news = $this->getAll();
        $news?->filter(function (Suivi|Affectation $new) use ($news, $signalement) {
            if ($signalement->getId() === $new->getSignalement()->getId() && $new instanceof Suivi)
                $news->removeElement($new);
        });
        return $news;
    }

    public function clear(): ArrayCollection|bool|null
    {
        $this->requestStack->getSession()->remove('lastActionTime');
        $news = $this->getAll();
        $news?->filter(function (Suivi|Affectation $new) use ($news) {
            $news->removeElement($new);
        });
        return $news;
    }
}