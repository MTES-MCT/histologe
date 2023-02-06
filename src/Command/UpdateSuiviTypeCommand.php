<?php

namespace App\Command;

use App\Entity\Suivi;
use App\Manager\SuiviManager;
use App\Repository\SuiviRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:suivi-type-update',
    description: 'Update Suivi type when null',
)]
class UpdateSuiviTypeCommand extends Command
{
    public function __construct(private SuiviManager $suiviManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var SuiviRepository $suiviRepository */
        $suiviRepository = $this->suiviManager->getRepository();
        $suivis = $suiviRepository->findBy([
            'type' => 0,
        ]);

        $count = 0;
        /** @var Suivi $suivi */
        foreach ($suivis as $suivi) {
            if (null === $suivi->getCreatedBy() || \in_array('ROLE_USAGER', $suivi->getCreatedBy()->getRoles())) {
                $suivi->setType(Suivi::TYPE_USAGER);
            } elseif ($this->isSuiviAuto($suivi)) {
                $suivi->setType(Suivi::TYPE_AUTO);
            } else {
                $suivi->setType(Suivi::TYPE_PARTNER);
            }
            $this->suiviManager->save($suivi, false);
            ++$count;
        }
        $this->suiviManager->flush();

        $io->success(sprintf('%s suivis updated', $count));

        return Command::SUCCESS;
    }

    private function isSuiviAuto(Suivi $suivi): bool
    {
        $description = $suivi->getDescription();

        if ('Signalement validé' === $description) {
            return true;
        }

        if ('Le signalement a été accepté' === $description) {
            return true;
        }

        if ('Modification du signalement par un partenaire' === $description) {
            return true;
        }

        if (0 === strpos($description, 'Le signalement à été refusé avec le motif suivant:')) {
            return true;
        }

        if (0 === strpos($description, 'Signalement rouvert pour ')) {
            return true;
        }

        if (0 === strpos($description, 'Signalement cloturé car non-valide avec le motif suivant :')) {
            return true;
        }

        if (preg_match('/Ajout de(.*)au signalement(.*)/', $description)) {
            return true;
        }

        if (preg_match('/Le signalement à été cloturé pour (.*)avec le motif suivant (.*)/', $description)) {
            return true;
        }

        if (preg_match('/Signalement (.*)via Esabora(.*)par(.*)/', $description)) {
            return true;
        }

        return false;
    }
}
