<?php

namespace App\Command;

use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Repository\SuiviRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:suivi-type-update',
    description: 'Update Suivi type when null',
)]
class UpdateSuiviTypeCommand extends Command
{
    private const FLUSH_COUNT = 1000;

    public function __construct(private SuiviManager $suiviManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ini_set("memory_limit", "-1"); // Hack for local env: uncomment this line if you have memory limit error

        $totalRead = 0;
        $io = new SymfonyStyle($input, $output);

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $this->suiviManager->getRepository();
        $suivis = $suiviRepository->findBy([
            'type' => 0,
        ]);

        $progressBar = new ProgressBar($output, \count($suivis));
        $progressBar->start();

        /** @var Suivi $suivi */
        foreach ($suivis as $suivi) {
            ++$totalRead;
            $progressBar->advance();

            if (null === $suivi->getCreatedBy() || \in_array('ROLE_USAGER', $suivi->getCreatedBy()->getRoles())) {
                $suivi->setType(Suivi::TYPE_USAGER);
            } elseif ($this->isSuiviAuto($suivi)) {
                $suivi->setType(Suivi::TYPE_AUTO);
            } else {
                $suivi->setType(Suivi::TYPE_PARTNER);
            }

            if (0 === $totalRead % self::FLUSH_COUNT) {
                $this->suiviManager->save($suivi);
            } else {
                $this->suiviManager->save($suivi, false);
            }
        }

        $this->suiviManager->flush();

        $progressBar->finish();
        $io->success(sprintf('%s suivis updated', $totalRead));

        return Command::SUCCESS;
    }

    private function isSuiviAuto(Suivi $suivi): bool
    {
        $description = $suivi->getDescription();

        if ('Signalement validé' === $description
        || 'Le signalement a été accepté' === $description
        || 'Modification du signalement par un partenaire' === $description
        || 0 === strpos($description, 'Le signalement à été refusé avec le motif suivant:')
        || 0 === strpos($description, 'Signalement rouvert pour ')
        || 0 === strpos($description, 'Signalement cloturé car non-valide avec le motif suivant :')
        || preg_match('/Ajout de(.*)au signalement(.*)/', $description)
        || preg_match('/Signalement (.*)via Esabora(.*)par(.*)/', $description)
        ) {
            return true;
        }

        return false;
    }
}
