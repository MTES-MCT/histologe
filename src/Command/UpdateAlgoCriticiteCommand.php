<?php

namespace App\Command;

use App\Entity\Critere;
use App\Repository\CritereRepository;
use App\Repository\CriticiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:algo-criticite-update',
    description: 'Update Criticité and Critère',
)]
class UpdateAlgoCriticiteCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CritereRepository $critereRepository,
        private CriticiteRepository $criticiteRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Update Critere and Criticite');

        $this->changeCritereCoefAndType(
            "L'entrée du bâtiment est abîmée ou mal sécurisée",
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0, 1, 2]
        );
        $this->changeCritereCoefAndType(
            'Le bâtiment n’est pas sécurisé contre les chutes.',
            coef: 2,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 1, 1],
            criticiteScores: [0.5, 1, 2]
        );
        $this->changeCritereCoefAndType(
            'L’installation du réseau électrique a un problème.',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [0.5, 0, 1.5]
        );
        $this->changeCritereCoefAndType(
            'L’installation du réseau gaz a un problème.',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [0, 0.5, 1.5]
        );
        $this->changeCritereCoefAndType(
            'La protection incendie n’est pas adaptée.',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 1, 1],
            criticiteScores: [0, 0.5, 1.5]
        );
        $this->changeCritereCoefAndType(
            'Le chauffage collectif est obsolète.',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 2]
        );
        $this->changeCritereCoefAndType(
            'Fils électriques dénudés',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 1, 1],
            criticiteScores: [0.5, 1, 1.5]
        );
        $this->changeCritereCoefAndType(
            'Les planchers sont dangereux.',
            coef: 2,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 1, 1],
            criticiteScores: [0, 2, 3]
        );
        $this->changeCritereCoefAndType(
            'Les garde-corps ou rambardes sont dangereuses.',
            coef: 2,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 2]
        );
        $this->changeCritereCoefAndType(
            "L'accès au logement est mal éclairé.",
            coef: 0,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0, 0, 0]
        );
        $this->changeCritereCoefAndType(
            'La protection incendie du logement n’est pas adaptée.',
            coef: 2,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 0.5]
        );
        $this->changeCritereCoefAndType(
            'Les escaliers intérieurs sont dangereux.',
            coef: 4,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 1, 1],
            criticiteScores: [0.5, 1, 3]
        );
        $this->changeCritereCoefAndType(
            'Les sols sont humides.',
            coef: 4,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [0.5, 1, 1]
        );
        $this->changeCritereCoefAndType(
            'Les murs ont des fissures.',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [1, 1, 6]
        );
        $this->changeCritereCoefAndType(
            "De l'eau s’infiltre dans mon logement.",
            coef: 4,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [1, 0, 1],
            criticiteScores: [0.5, 0.5, 1.5]
        );
        $this->changeCritereCoefAndType(
            'Il y a des traces importantes de moisissures.',
            coef: 2,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [1, 1, 4.5]
        );
        $this->changeCritereCoefAndType(
            'La peinture est écaillée par endroits.',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [0.5, 1, 6]
        );
        $this->changeCritereCoefAndType(
            'Les toilettes du logement sont abîmées ou inexistantes.',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 2]
        );
        $this->changeCritereCoefAndType(
            'La salle de bain est abîmée ou inexistante.',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 2, 4]
        );
        $this->changeCritereCoefAndType(
            "J'ai un problème avec l’eau potable.",
            coef: 3,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1.67, 1.67]
        );
        $this->changeCritereCoefAndType(
            'Il y a des nuisibles dans mon logement (blattes, punaises de lit, rongeurs...).',
            coef: 0,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0, 0, 0]
        );
        $this->changeCritereCoefAndType(
            "J’ai un problème d'évacuation des eaux usées.",
            coef: 2,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 3]
        );
        $this->changeCritereCoefAndType(
            'Les installations électriques ne sont pas en bon état.',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 1, 1],
            criticiteScores: [0, 1, 2]
        );
        $this->changeCritereCoefAndType(
            'Le chauffage ne fonctionne pas bien.',
            coef: 4,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [1, 2, 1]
        );
        $this->changeCritereCoefAndType(
            "J'ai un problème de ventilation dans mon logement.",
            coef: 2,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [1, 2, 3]
        );
        $this->changeCritereCoefAndType(
            'De l’air s’infiltre dans mon logement.',
            coef: 4,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 1]
        );
        $this->changeCritereCoefAndType(
            'Mes factures de chauffage sont anormalement élevées.',
            coef: 2,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [1, 1, 1]
        );
        $this->changeCritereCoefAndType(
            'Mon logement est mal isolé.',
            coef: 2,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [1, 1, 1]
        );
        $this->changeCritereCoefAndType(
            "J’utilise un appareil d'appoint pour le chauffage.",
            coef: 2,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 1]
        );
        $this->changeCritereCoefAndType(
            "L'installation de gaz n’est pas en bon état",
            coef: 2,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 1, 1],
            criticiteScores: [0, 1, 3]
        );
        $this->changeCritereCoefAndType(
            'Le sol est abîmé.',
            coef: 4,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [0.5, 0.5, 2]
        );
        $this->changeCritereCoefAndType(
            'Les escaliers sont abîmés.',
            coef: 2,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [1, 2, 3]
        );
        $this->changeCritereCoefAndType(
            'Les murs et plafonds intérieurs sont mal entretenus',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 2]
        );
        $this->changeCritereCoefAndType(
            'Les façades ne sont pas en bon état.',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 1],
            criticiteScores: [0.5, 2, 6]
        );
        $this->changeCritereCoefAndType(
            'La toiture n’est pas en bon état.',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [1, 2, 3]
        );
        $this->changeCritereCoefAndType(
            'Mon logement est en sous-sol.',
            coef: 8,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0],
            criticiteScores: [3]
        );
        $this->changeCritereCoefAndType(
            'Mon logement est sous les combles.',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 2]
        );
        $this->changeCritereCoefAndType(
            "Il n'y a pas de fenêtre dans mon salon ou ma salle à manger.",
            coef: 8,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0],
            criticiteScores: [0.5]
        );
        $this->changeCritereCoefAndType(
            'Mon logement est une pièce unique, de moins de 9m².',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0],
            criticiteScores: [3]
        );
        $this->changeCritereCoefAndType(
            'Mon salon ou ma salle à manger mesure moins de 7m².',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0],
            criticiteScores: [1]
        );
        $this->changeCritereCoefAndType(
            'Les plafonds sont trop bas (moins de 2.20 m).',
            coef: 2,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [1],
            criticiteScores: [2]
        );
        $this->changeCritereCoefAndType(
            'La lumière naturelle en pleine journée est insuffisante.',
            coef: 8,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0, 0.25, 0.5]
        );
        $this->changeCritereCoefAndType(
            'Les déchets sont mal stockés.',
            coef: 1,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0, 1, 3]
        );
        $this->changeCritereCoefAndType(
            'L’usage des lieux n’est pas respecté.',
            coef: 2,
            type: Critere::TYPE_BATIMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0, 0, 1]
        );
        $this->changeCritereCoefAndType(
            'Le bruit à l’intérieur du logement ou du bâtiment est gênant.',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 1, 2]
        );
        $this->changeCritereCoefAndType(
            'Le bruit extérieur est gênant.',
            coef: 1,
            type: Critere::TYPE_LOGEMENT,
            criticiteIsDanger: [0, 0, 0],
            criticiteScores: [0.5, 0.5, 1]
        );

        $this->io->success('End of change Critere and Criticites coef, score, isDanger and types');

        $this->changeCritereLabel(
            'L’installation du réseau électrique a un problème.',
            'L’installation du réseau électrique du bâtiment a un problème'
        );
        $this->changeCritereLabel(
            'Fils électriques dénudés',
            'Fils électriques dénudés -Bâtiment/Parties communes'
        );
        $this->io->success('End of change Critere labels');

        return Command::SUCCESS;
    }

    private function changeCritereCoefAndType(string $label, int $coef, int $type, ?array $criticiteIsDanger, ?array $criticiteScores)
    {
        $critere = $this->critereRepository->findByLabel($label);
        if ($critere) {
            $this->io->section('<info>Update critere with label</info> : '.$critere->getLabel());
            $critere->setNewCoef($coef);
            $critere->setType($type);
            $this->io->text(sprintf('New critere coef : %s and critere type : %s', $critere->getNewCoef(), $critere->getTypeString()));
            $this->io->newLine();

            if ($criticiteIsDanger || $criticiteScores) {
                $this->changeCriticiteScore($critere, $criticiteIsDanger, $criticiteScores);
            }

            $this->entityManager->persist($critere);
            $this->entityManager->flush();
        } else {
            $this->io->warning('No critere with label : '.$label);
        }
    }

    private function changeCriticiteScore(Critere $critere, ?array $criticiteIsDanger, ?array $criticiteScores)
    {
        $criticites = $this->criticiteRepository->findBy(
            ['critere' => $critere,
            'isArchive' => 0,
            ],
            ['id' => 'ASC']
        );

        if ($criticites) {
            $count = 0;
            foreach ($criticites as $criticite) {
                $this->io->text('<info>Update criticite with label</info> : '.$criticite->getLabel());
                $criticite->setIsDanger($criticiteIsDanger[$count]);
                $criticite->setNewScore($criticiteScores[$count]);
                $this->io->text(sprintf('IsDanger : %s and new criticite score : %s', var_export($criticite->getIsDanger(), true), $criticite->getNewScore()));

                $this->entityManager->persist($criticite);
                ++$count;
            }

            $this->entityManager->flush();
        } else {
            $this->io->warning('No criticites associated with critere with label : '.$critere->getLabel());
        }
    }

    private function changeCritereLabel(string $oldLabel, string $newLabel)
    {
        $critere = $this->critereRepository->findByLabel($oldLabel);
        if ($critere) {
            $this->io->text('Update critere with label : '.$critere->getLabel());
            $critere->setLabel($newLabel);
            $this->io->text('New Label : '.$critere->getLabel());

            $this->entityManager->persist($critere);
            $this->entityManager->flush();
        } else {
            $this->io->warning('No critere with label : '.$oldLabel);
        }
    }
}
