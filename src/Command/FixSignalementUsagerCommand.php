<?php

namespace App\Command;

use App\Entity\Enum\UserStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Manager\SignalementUsagerManager;
use App\Manager\UserManager;
use App\Repository\SignalementRepository;
use App\Repository\SignalementUsagerRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-signalement-usager',
    description: 'Vérifie et répare les usagers (occupant et déclarant) liés à un signalement via signalement_usager',
)]
class FixSignalementUsagerCommand extends Command
{
    // Préfixe utilisé dans UserManager pour créer des usagers de type occupant avec des mails vides
    private const string PLACEHOLDER_EMAIL_PREFIX = 'sl__';

    /** @var string[] */
    private array $changes = [];
    private bool $dryRun = false;

    public function __construct(
        private readonly SignalementRepository $signalementRepository,
        private readonly SignalementUsagerRepository $signalementUsagerRepository,
        private readonly SignalementUsagerManager $signalementUsagerManager,
        private readonly UserRepository $userRepository,
        private readonly UserFactory $userFactory,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('uuid', InputArgument::REQUIRED, 'UUID du signalement')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simule les modifications sans les enregistrer');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->changes = [];
        $this->dryRun = (bool) $input->getOption('dry-run');

        $uuid = trim((string) $input->getArgument('uuid'));
        /** @var Signalement|null $signalement */
        $signalement = $this->signalementRepository->findOneBy(['uuid' => $uuid]);
        if (null === $signalement) {
            $io->error(sprintf('Aucun signalement trouvé avec l\'uuid "%s".', $uuid));

            return Command::FAILURE;
        }

        $io->title(sprintf(
            'Signalement %s (uuid %s)',
            $signalement->getReference(),
            $signalement->getUuid()
        ));
        if ($this->dryRun) {
            $io->note('Mode simulation (--dry-run) : aucune modification ne sera enregistrée.');
        }

        $this->entityManager->beginTransaction();
        try {
            $this->processUsager($io, $signalement, UserManager::OCCUPANT);
            $this->processUsager($io, $signalement, UserManager::DECLARANT);

            if ($this->dryRun) {
                $this->entityManager->rollback();
            } else {
                $this->entityManager->commit();
            }
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            $io->error(sprintf('Erreur, aucune modification enregistrée : %s', $exception->getMessage()));

            return Command::FAILURE;
        }

        $io->section('Récapitulatif');
        if (empty($this->changes)) {
            $io->success('Aucune modification effectuée.');

            return Command::SUCCESS;
        }
        $io->listing($this->changes);
        if ($this->dryRun) {
            $io->warning('Mode simulation : ces modifications ont été annulées.');
        } else {
            foreach ($this->changes as $change) {
                $this->logger->info(sprintf('[app:fix-signalement-usager] %s : %s', $signalement->getUuid(), $change));
            }
            $io->success(sprintf('%d modification(s) enregistrée(s).', \count($this->changes)));
        }

        return Command::SUCCESS;
    }

    private function processUsager(SymfonyStyle $io, Signalement $signalement, string $type): void
    {
        $io->section(sprintf('Usager %s', $type));

        $isOccupant = UserManager::OCCUPANT === $type;
        $mail = trim((string) ($isOccupant ? $signalement->getMailOccupant() : $signalement->getMailDeclarant()));
        $prenom = $isOccupant ? $signalement->getPrenomOccupant() : $signalement->getPrenomDeclarant();
        $nom = $isOccupant ? $signalement->getNomOccupant() : $signalement->getNomDeclarant();
        $signalementUsager = $signalement->getSignalementUsager();
        $linkedUser = $isOccupant ? $signalementUsager?->getOccupant() : $signalementUsager?->getDeclarant();

        $io->table(
            ['', 'Données du signalement', $linkedUser ? sprintf('User lié #%d', $linkedUser->getId()) : 'User lié'],
            [
                ['E-mail', $mail ?: '-', $linkedUser?->getEmail() ?? '-'],
                ['Prénom', $prenom ?? '-', $linkedUser?->getPrenom() ?? '-'],
                ['Nom', $nom ?? '-', $linkedUser?->getNom() ?? '-'],
                ['Rôles', '', $linkedUser ? implode(', ', $linkedUser->getRoles()) : '-'],
                ['Statut', '', $linkedUser?->getStatut()->value ?? '-'],
            ]
        );

        if ('' === $mail) {
            if ($isOccupant && $linkedUser && str_starts_with($linkedUser->getEmail(), self::PLACEHOLDER_EMAIL_PREFIX)) {
                $io->success('Pas d\'e-mail occupant dans le signalement, le user lié porte un e-mail technique : cohérent.');

                return;
            }
            $io->note(sprintf('Pas d\'e-mail %s dans le signalement, aucune vérification possible.', $type));

            return;
        }

        // Comparaison des données du signalement avec celles du user lié (s'il existe)
        $emailMatches = null !== $linkedUser && $this->normalize($linkedUser->getEmail()) === $this->normalize($mail);
        $identityMatches = null !== $linkedUser
            && $this->normalize($linkedUser->getPrenom()) === $this->normalize($prenom)
            && $this->normalize($linkedUser->getNom()) === $this->normalize($nom);

        if (null === $linkedUser) {
            $io->warning(sprintf('Aucun user n\'est lié au signalement en tant que %s.', $type));
        } elseif ($emailMatches && $identityMatches) {
            $io->success(sprintf('Le user lié #%d correspond aux données du signalement.', $linkedUser->getId()));

            return;
        } elseif ($emailMatches) {
            $io->warning(sprintf('L\'e-mail du user lié #%d correspond, mais le prénom ou le nom diffère.', $linkedUser->getId()));
        } else {
            $io->warning(sprintf(
                'L\'e-mail du user lié #%d (%s) ne correspond pas à l\'e-mail %s du signalement (%s).',
                $linkedUser->getId(),
                $linkedUser->getEmail(),
                $type,
                $mail
            ));
        }

        /** @var User|null $userWithSameEmail */
        $userWithSameEmail = $this->userRepository->findOneBy(['email' => $mail]);
        if (null !== $linkedUser && $userWithSameEmail === $linkedUser) {
            // Correspondance au sens de MySQL (collation) même si la comparaison PHP diffère
            $emailMatches = true;
        }

        // Demandes de remplacement des informations du user par celles du signalement
        if (null !== $linkedUser) {
            $blockingReasons = $this->getUpdateBlockingReasons($signalement, $type, $linkedUser, $userWithSameEmail);
            if (empty($blockingReasons)) {
                $question = sprintf(
                    'Faut-il remplacer les informations du user #%d par celles du signalement (prénom : %s, nom : %s, e-mail : %s) ?',
                    $linkedUser->getId(),
                    $prenom ?? '-',
                    $nom ?? '-',
                    $mail
                );
                if ($io->confirm($question, false)) {
                    $this->updateUser($io, $linkedUser, $prenom, $nom, $mail);

                    return;
                }
                if ($emailMatches) {
                    $io->warning(sprintf('Modification refusée : aucune modification effectuée pour l\'usager %s.', $type));

                    return;
                }
            } else {
                $io->caution(array_merge(
                    [sprintf('Le remplacement des informations du user #%d par celles du signalement est bloqué.', $linkedUser->getId(), $prenom ?? '-', $nom ?? '-', $mail)],
                    $blockingReasons
                ));
                if ($emailMatches) {
                    $io->warning(sprintf('Aucune modification effectuée pour l\'usager %s : correction manuelle nécessaire.', $type));

                    return;
                }
            }
        }

        // On n'a pas remplacé les informations du user, mais on propose de lier à un user existant
        if (null !== $userWithSameEmail) {
            $this->displayUser($io, sprintf('Un user existe déjà avec l\'e-mail %s', $mail), $userWithSameEmail);
            $question = sprintf(
                'Voulez-vous lier le user #%d avec le signalement en tant que %s ?',
                $userWithSameEmail->getId(),
                $type
            );
            if ($io->confirm($question, false)) {
                $this->linkUser($io, $signalement, $type, $userWithSameEmail, $linkedUser);

                return;
            }
            $io->warning(sprintf(
                'Liaison refusée : le user #%d n\'a pas été lié. Aucune modification effectuée pour l\'usager %s.',
                $userWithSameEmail->getId(),
                $type
            ));

            return;
        }

        // On n'a pas remplacé les informations du user, on propose de créer un user de rôle USAGER
        $io->text(sprintf('Aucun user n\'existe avec l\'e-mail %s.', $mail));
        $question = sprintf(
            'Faut-il créer un nouvel usager (prénom : %s, nom : %s, e-mail : %s, rôle : %s) et le lier au signalement en tant que %s ?',
            $prenom ?? '-',
            $nom ?? '-',
            $mail,
            User::ROLE_USAGER,
            $type
        );
        if ($io->confirm($question, false)) {
            $newUser = $this->userFactory->createInstanceFrom(
                roleLabel: User::ROLE_USAGER,
                firstname: $prenom,
                lastname: $nom,
                email: $mail,
            );
            $this->entityManager->persist($newUser);
            $this->linkUser($io, $signalement, $type, $newUser, $linkedUser, true);

            return;
        }
        $io->warning(sprintf('Création refusée : aucune modification effectuée pour l\'usager %s.', $type));
    }

    /**
     * @return string[]
     */
    private function getUpdateBlockingReasons(
        Signalement $signalement,
        string $type,
        User $linkedUser,
        ?User $userWithSameEmail,
    ): array {
        $reasons = [];
        if (!$linkedUser->isUsager()) {
            $reasons[] = sprintf(
                'Ce user n\'est pas un usager (rôles : %s) : modifier son e-mail changerait ses identifiants de connexion.',
                implode(', ', $linkedUser->getRoles())
            );
        }

        $otherLinks = $this->getOtherLinks($linkedUser, $signalement, $type);
        if (!empty($otherLinks)) {
            $reasons[] = sprintf(
                'Ce user est aussi lié à : %s. Le modifier transférerait ces liens à une autre personne.',
                implode(', ', $otherLinks)
            );
        }

        if (null !== $userWithSameEmail && $userWithSameEmail !== $linkedUser) {
            $reasons[] = sprintf(
                'L\'e-mail du signalement est déjà utilisé par le user #%d.',
                $userWithSameEmail->getId()
            );
        }

        return $reasons;
    }

    /**
     * Liens du user, hors celui en cours de traitement (signalement + type).
     *
     * @return string[]
     */
    private function getOtherLinks(User $user, ?Signalement $excludedSignalement = null, ?string $excludedType = null): array
    {
        $links = [];
        foreach ($this->signalementUsagerRepository->findByUser($user) as $signalementUsager) {
            $linkedSignalement = $signalementUsager->getSignalement();
            $isExcludedSignalement = $linkedSignalement === $excludedSignalement;
            if ($signalementUsager->getOccupant() === $user
                && !($isExcludedSignalement && UserManager::OCCUPANT === $excludedType)
            ) {
                $links[] = sprintf('occupant du signalement %s (%s)', $linkedSignalement?->getReference(), $linkedSignalement?->getUuid());
            }
            if ($signalementUsager->getDeclarant() === $user
                && !($isExcludedSignalement && UserManager::DECLARANT === $excludedType)
            ) {
                $links[] = sprintf('déclarant du signalement %s (%s)', $linkedSignalement?->getReference(), $linkedSignalement?->getUuid());
            }
        }

        return $links;
    }

    private function updateUser(SymfonyStyle $io, User $user, ?string $prenom, ?string $nom, string $mail): void
    {
        $before = sprintf('%s %s <%s>', $user->getPrenom(), $user->getNom(), $user->getEmail());
        $user->setPrenom($prenom)->setNom($nom)->setEmail($mail);
        $this->entityManager->flush();
        $after = sprintf('%s %s <%s>', $user->getPrenom(), $user->getNom(), $user->getEmail());

        $change = sprintf('User #%d modifié : %s → %s', $user->getId(), $before, $after);
        $this->changes[] = $change;
        $io->success($change);
    }

    private function linkUser(
        SymfonyStyle $io,
        Signalement $signalement,
        string $type,
        User $user,
        ?User $previousUser,
        bool $isNewUser = false,
    ): void {
        $isOccupant = UserManager::OCCUPANT === $type;
        $this->signalementUsagerManager->createOrUpdate(
            $signalement,
            $isOccupant ? $user : null,
            $isOccupant ? null : $user
        );
        $this->entityManager->flush();

        $change = sprintf(
            '%sUser #%d (%s) lié en tant que %s (précédemment : %s)',
            $isNewUser ? 'Nouvel usager créé. ' : '',
            $user->getId(),
            $user->getEmail(),
            $type,
            $previousUser ? sprintf('user #%d, %s', $previousUser->getId(), $previousUser->getEmail()) : 'aucun'
        );
        $this->changes[] = $change;
        $io->success($change);

        if (null !== $previousUser && empty($this->getOtherLinks($previousUser))) {
            $io->note(sprintf(
                'L\'ancien user #%d n\'est plus lié à aucun signalement. Il n\'a pas été supprimé.',
                $previousUser->getId()
            ));
        }
    }

    private function displayUser(SymfonyStyle $io, string $title, User $user): void
    {
        $io->text($title.' :');
        $otherLinks = $this->getOtherLinks($user);
        $io->table(
            ['Id', 'E-mail', 'Prénom', 'Nom', 'Rôles', 'Statut', 'Signalements liés'],
            [[
                $user->getId(),
                $user->getEmail(),
                $user->getPrenom() ?? '-',
                $user->getNom() ?? '-',
                implode(', ', $user->getRoles()),
                $user->getStatut()->value,
                \count($otherLinks),
            ]]
        );
        if (!$user->isUsager()) {
            $io->caution(sprintf('Le user #%d n\'est pas un usager (rôles : %s).', $user->getId(), implode(', ', $user->getRoles())));
        }
        if (UserStatus::ARCHIVE === $user->getStatut()) {
            $io->caution(sprintf('Le user #%d est archivé.', $user->getId()));
        }
    }

    private function normalize(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }
}
