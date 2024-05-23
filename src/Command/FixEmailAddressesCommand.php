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
    private const STRINGS_TO_REPLACE = [
        ',com' => '.com',
        ',fr' => '.fr',
        ',net' => '.net',
        '?com' => '.com',
        '?fr' => '.fr',
        '?net' => '.net',
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
        $count = 0;
        $listSearch = array_keys(self::STRINGS_TO_REPLACE);
        $listReplace = array_values(self::STRINGS_TO_REPLACE);
        foreach (self::FIELDS as $field) {
            $listSignalementsToNull = $this->signalementRepository->findByEmailContainStrings(self::EMAILS_TO_NULL, $field, true);
            $count += \count($listSignalementsToNull);
            $this->fixEmailsWithValue($listSignalementsToNull, $field, null);

            $listSignalementsToInconnu = $this->signalementRepository->findByEmailContainStrings(self::EMAILS_TO_INCONNU, $field, true);
            $count += \count($listSignalementsToInconnu);
            $this->fixEmailsWithValue($listSignalementsToInconnu, $field, 'inconnu@histologe.fr');

            $listSignalementsToReplace = $this->signalementRepository->findByEmailContainStrings($listSearch, $field);
            $count += \count($listSignalementsToReplace);
            $this->fixEmailsWithReplace($listSignalementsToReplace, $field, $listSearch, $listReplace);
        }

        $this->io->success(sprintf('%s e-mail addresses were successfully fixed.', $count));

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

    private function fixEmailsWithReplace(array $listSignalements, string $field, array $listSearch, array $listReplace): void
    {
        /** @var Signalement $signalement */
        foreach ($listSignalements as $signalement) {
            switch ($field) {
                case 'mailOccupant':
                    $signalement->setMailOccupant(str_replace($listSearch, $listReplace, $signalement->getMailOccupant()));
                    break;
                case 'mailDeclarant':
                    $signalement->setMailDeclarant(str_replace($listSearch, $listReplace, $signalement->getMailDeclarant()));
                    break;
                case 'mailProprio':
                    $signalement->setMailProprio(str_replace($listSearch, $listReplace, $signalement->getMailProprio()));
                    break;
            }
            $this->signalementManager->save($signalement);
        }
    }
}
