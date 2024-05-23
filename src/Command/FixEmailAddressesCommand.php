<?php

namespace App\Command;

use App\Entity\Signalement;
use App\Manager\SignalementManager;
use App\Repository\SignalementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'app:fix-email-addresses',
    description: 'Fix e-mail addresses'
)]
class FixEmailAddressesCommand extends Command
{
    private SymfonyStyle $io;

    private const FIELDS = ['mailOccupant', 'mailDeclarant', 'mailProprio'];
    private const EMAILS_TO_NULL = [
        'Non communiqué',
        '?',
        '??',
    ];
    private const EMAILS_TO_INCONNU = [
        'inconnu@inconnu.com',
        'inconnu@inconnu',
        'email@inconnu',
        'test@test',
        'x@x.com',
        'test@fr',
        'x@x.xx',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private SignalementRepository $signalementRepository,
        private SignalementManager $signalementManager,
    ) {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        // See https://symfony.com/doc/current/console/style.html
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach (self::FIELDS as $field) {
            $listSignalements = $this->signalementRepository->findUsersByEmailContainString(self::EMAILS_TO_NULL, $field);
            $this->fixEmailsWithValue($listSignalements, $field, null);

            $listSignalements = $this->signalementRepository->findUsersByEmailContainString(self::EMAILS_TO_INCONNU, $field);
            $this->fixEmailsWithValue($listSignalements, $field, 'inconnu@histologe.fr');
        }

        $this->io->success('E-mail addresses were successfully fixed.');

        return Command::SUCCESS;
    }

    private function fixEmailsWithValue(array $listSignalements, string $field, ?string $newValue): void
    {
        /** @var Signalement $signalement */
        foreach ($listSignalements as $signalement) {
            switch ($field) {
                case 'mailOccupant':
                    $signalement->setMailOccupant($newValue);
                    break;
                case 'mailDeclarant':
                    $signalement->setMailDeclarant($newValue);
                    break;
                case 'mailProprio':
                    $signalement->setMailProprio($newValue);
                    break;
            }
            $this->signalementManager->save($signalement);
        }
    }
}
