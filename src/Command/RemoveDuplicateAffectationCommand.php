<?php

namespace App\Command;

use App\Repository\AffectationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:remove-duplicate-affectation',
    description: 'Remove duplicate affectations',
)]
class RemoveDuplicateAffectationCommand
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly AffectationRepository $affectationRepository,
    ) {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $sql = "SELECT GROUP_CONCAT(CONCAT(id, '-', statut)) as duplicates FROM affectation GROUP BY signalement_id, partner_id HAVING COUNT(*) > 1;";
        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $res = $stmt->executeQuery();
        $list = $res->fetchAllAssociative();

        foreach ($list as $item) {
            $duplicates = explode(',', $item['duplicates']);
            $i = 0;
            $msg = '';
            foreach ($duplicates as $duplicate) {
                $parts = explode('-', $duplicate);
                // dans tous les cas on garde uniquement la première affectation (qui est toujours celle en cours quand des statuts différents existent)
                if ($i > 0) {
                    $affectation = $this->affectationRepository->find($parts[0]);
                    $this->entityManager->remove($affectation);
                    $this->entityManager->flush();
                    if ($i > 1) {
                        $msg .= ' / ';
                    }
                    $msg .= 'Affectation '.$duplicate.' supprimée';
                } else {
                    $msg .= 'Affectation '.$duplicate.' conservée. [';
                }
                ++$i;
            }
            $io->info($msg.']');
        }
        // ajout de la contrainte d'unicité
        $this->entityManager->getConnection()->executeStatement(
            'CREATE UNIQUE INDEX unique_affectation_signalement_partner ON affectation (signalement_id, partner_id)'
        );

        return Command::SUCCESS;
    }
}
