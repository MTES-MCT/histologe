<?php

namespace App\DataFixtures\Loader;

use App\Entity\Enum\PartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Repository\BailleurRepository;
use App\Repository\TerritoryRepository;
use App\Service\Sanitizer;
use App\Service\Token\TokenGeneratorInterface;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Yaml\Yaml;

class LoadPartnerData extends Fixture implements OrderedFixtureInterface
{
    public function __construct(
        private TerritoryRepository $territoryRepository,
        private BailleurRepository $bailleurRepository,
        private TokenGeneratorInterface $tokenGenerator,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $partnersRows = Yaml::parseFile(__DIR__.'/../Files/Partner.yml');
        foreach ($partnersRows['partners'] as $row) {
            $this->loadPartner($manager, $row);
        }
        $manager->flush();
    }

    public function loadPartner(ObjectManager $manager, array $row): void
    {
        $partner = (new Partner())
            ->setNom($row['nom'])
            ->setIsArchive($row['is_archive'])
            ->setIsEsaboraActive($row['is_esabora_active'] ?? false);

        if ($row['is_archive'] && null !== $row['email']) {
            $partner->setEmail(Sanitizer::tagArchivedEmail($row['email']));
        } else {
            $partner->setEmail($row['email'] ?? null);
        }

        if (isset($row['insee'])) {
            $partner->setInsee(json_decode($row['insee'], true));
        }

        $partner->setTerritory($this->territoryRepository->findOneBy(['name' => $row['territory']]));

        if (isset($row['esabora_url'])) {
            $partner->setEsaboraUrl($row['esabora_url'])->setEsaboraToken($this->tokenGenerator->generateToken());
        }

        if (isset($row['type'])) {
            $partner->setType(PartnerType::from($row['type']));
        }

        if (isset($row['bailleur'])) {
            $partner->setBailleur($this->bailleurRepository->findOneBailleurBy($row['bailleur'], $partner->getTerritory()));
        }

        if (isset($row['competence'])) {
            $competences = [];
            if (\is_array($row['competence'])) {
                foreach ($row['competence'] as $competence) {
                    $competences[] = Qualification::tryFrom($competence);
                }
            } else {
                $competences[] = Qualification::tryFrom($row['competence']);
            }
            $partner->setCompetence($competences);
        }

        if (isset($row['is_idoss_active'])) {
            $partner->setIsIdossActive($row['is_idoss_active']);
        }
        if (isset($row['idoss_url'])) {
            $partner->setIdossUrl($row['idoss_url']);
        }

        $manager->persist($partner);
    }

    public function getOrder(): int
    {
        return 7;
    }
}
