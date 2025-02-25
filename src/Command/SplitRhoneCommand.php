<?php

namespace App\Command;

use App\Entity\Partner;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Manager\HistoryEntryManager;
use App\Repository\PartnerRepository;
use App\Repository\TagRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use App\Service\Signalement\ZipcodeProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:split-rhone',
    description: 'Set partners, affectations, interventions, users and tags consistents after split rhone territory into Metropole de Lyon and Rhone',
)]
class SplitRhoneCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TerritoryRepository $territoryRepository,
        private readonly PartnerRepository $partnerRepository,
        private readonly ValidatorInterface $validator,
        private readonly HistoryEntryManager $historyEntryManager,
        private readonly TagRepository $tagRepository,
        private readonly UserRepository $userRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->historyEntryManager->removeEntityListeners();
        $io = new SymfonyStyle($input, $output);

        $rhone = $this->territoryRepository->findOneBy(['zip' => ZipcodeProvider::RHONE_CODE_DEPARTMENT_69]);
        $metropoleDeLyon = $this->territoryRepository->findOneBy(['zip' => ZipcodeProvider::METROPOLE_LYON_CODE_DEPARTMENT_69A]);

        $partners = $this->partnerRepository->getWithoutInseeForTerritory(ZipcodeProvider::RHONE_CODE_DEPARTMENT_69);
        foreach ($partners as $partner) {
            // to prevent validation error "Un partenaire existe déjà avec cette adresse e-mail." on add user
            if (in_array($partner->getEmail(), ['ddt-shru-his@rhone.gouv.fr', 'incurie@habiter.org', 'territoire2@rhone.fr', 'pigoullins@urbanis.fr'])) {
                $partner->setEmail(null);
            }
        }
        $this->entityManager->flush();
        foreach ($partners as $partner) {
            $copy = new Partner();
            $copy
                ->setNom($partner->getNom())
                ->setIsArchive($partner->getIsArchive())
                ->setInsee($partner->getInsee())
                ->setEmail($partner->getEmail())
                ->setEsaboraUrl($partner->getEsaboraUrl())
                ->setEsaboraToken($partner->getEsaboraToken())
                ->setTerritory($metropoleDeLyon)
                ->setType($partner->getType())
                ->setCompetence($partner->getCompetence())
                ->setIsEsaboraActive($partner->isEsaboraActive());

            $this->entityManager->persist($copy);
            $io->text('Copy partner "'.$partner->getNom().'" to "'.$metropoleDeLyon->getName().'"');

            $nbAffectations = 0;
            foreach ($partner->getAffectations() as $affectation) {
                if ($affectation->getTerritory() === $metropoleDeLyon) {
                    $affectation->setPartner($copy);
                }
            }

            $nbInterventions = 0;
            foreach ($partner->getInterventions() as $intervention) {
                if ($intervention->getSignalement()->getTerritory() === $metropoleDeLyon) {
                    $intervention->setPartner($copy);
                    ++$nbInterventions;
                }
            }
            $io->text('- Change '.$nbAffectations.' affectations and '.$nbInterventions.' interventions from "'.$partner->getNom().'"');

            foreach ($partner->getUsers() as $user) {
                // to prevent exception "More than one result was found for query although one row or none was expected." because 2 users with same email already exist in DB
                if ('clemence.fagot@urbanis.fr' === $user->getEmail()) {
                    $io->text('- Skip user '.$user->getEmail());
                    continue;
                }
                // to prevent validation error "Un utilisateur ayant les droits d'affectation existe déjà avec cette adresse e-mail."
                if ($user->hasPermissionAffectation()) {
                    $user->setHasPermissionAffectation(false);
                    $io->text('- Remove permission affectation for user '.$user->getEmail());
                }

                $userTmp = (new User())->setEmail($user->getEmail());
                $userPartner = (new UserPartner())->setUser($userTmp)->setPartner($copy);
                $userTmp->addUserPartner($userPartner);

                $errors = $this->validator->validate($userTmp, null, ['user_partner_mail_multi']);
                if (\count($errors) > 0) {
                    $io->error((string) $errors.' for user '.$user->getEmail());

                    return Command::FAILURE;
                }
                $userPartner->setUser($user);
                $this->entityManager->persist($userPartner);
                $io->text('- Add user "'.$user->getEmail().'" to "'.$copy->getNom().'" "'.$metropoleDeLyon->getName().'"');
            }
        }
        $this->entityManager->flush();

        $tags = $this->tagRepository->findBy(['territory' => $rhone]);
        foreach ($tags as $tag) {
            $tagTerritories = [];
            foreach ($tag->getSignalements() as $signalement) {
                $tagTerritories[$signalement->getTerritory()->getId()] = $signalement->getTerritory();
            }
            if (1 === \count($tagTerritories)) {
                $tag->setTerritory($tagTerritories[array_key_first($tagTerritories)]);
            }
            if (\count($tagTerritories) > 1) {
                $copy = new Tag();
                $copy
                    ->setLabel($tag->getLabel())
                    ->setIsArchive($tag->getIsArchive())
                    ->setTerritory($metropoleDeLyon);

                $this->entityManager->persist($copy);
                foreach ($tag->getSignalements() as $signalement) {
                    if ($signalement->getTerritory() === $metropoleDeLyon) {
                        $signalement->addTag($copy);
                        $signalement->removeTag($tag);
                    }
                }
            }
        }
        $this->entityManager->flush();
        // final update : we set role RT to user "ddt-shru-his@rhone.gouv.fr"
        $user = $this->userRepository->findOneBy(['email' => 'ddt-shru-his@rhone.gouv.fr']);
        $user->setRoles(['ROLE_ADMIN_TERRITORY']);
        $this->entityManager->flush();

        $io->success('Partners, affectations, interventions, users and tags are consistents.');

        return Command::SUCCESS;
    }
}
