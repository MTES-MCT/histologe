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
        // ini_set("memory_limit", "-1"); // Hack for local env: uncomment this line if you have memory limit error

        $totalRead = 0;
        $this->io = new SymfonyStyle($input, $output);

        $this->io->title('Update Critere and Criticite');

        $this->changeCritereCoefAndType("L'entrée du bâtiment est abîmée ou mal sécurisée", coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0, 1, 2]);
        $this->changeCritereCoefAndType('Le bâtiment n’est pas sécurisé contre les chutes.', coef: 2, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 1, 1], criticiteScores: [0.5, 1, 2]);
        $this->changeCritereCoefAndType('L’installation du réseau électrique a un problème.', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [0, 0.5, 1.5]);
        $this->changeCritereCoefAndType('L’installation du réseau gaz a un problème.', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [0, 0.5, 1.5]);
        $this->changeCritereCoefAndType('La protection incendie n’est pas adaptée.', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 1, 1], criticiteScores: [0, 0.5, 1.5]);
        $this->changeCritereCoefAndType('Le chauffage collectif est obsolète.', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 2]);
        $this->changeCritereCoefAndType('Fils électriques dénudés', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 1, 1], criticiteScores: [0.5, 1, 1.5]);
        $this->changeCritereCoefAndType('Les planchers sont dangereux.', coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 1, 1], criticiteScores: [0, 2, 3]);
        $this->changeCritereCoefAndType('Les garde-corps ou rambardes sont dangereuses.', coef: 2, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 2]);
        $this->changeCritereCoefAndType("L'accès au logement est mal éclairé.", coef: 0, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0, 0, 0]);
        $this->changeCritereCoefAndType('La protection incendie du logement n’est pas adaptée.', coef: 2, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 0.5]);
        $this->changeCritereCoefAndType('Les escaliers intérieurs sont dangereux.', coef: 4, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 1, 1], criticiteScores: [0.5, 1, 3]); // TODO, coef différent ?
        $this->changeCritereCoefAndType('Les sols sont humides.', coef: 4, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [0.5, 1, 1]);
        $this->changeCritereCoefAndType('Les murs ont des fissures.', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [1, 1, 2]); // TODO : coef et type
        $this->changeCritereCoefAndType("De l'eau s’infiltre dans mon logement.", coef: 4, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [1, 0, 1], criticiteScores: [0.5, 0.5, 1.5]);
        $this->changeCritereCoefAndType('Il y a des traces importantes de moisissures.', coef: 4, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [0.5, 0.5, 1.5]); // TODO coef différent
        $this->changeCritereCoefAndType('La peinture est écaillée par endroits.', coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [0.5, 1, 2]); // TODO coef différent
        $this->changeCritereCoefAndType('Les toilettes du logement sont abîmées ou inexistantes.', coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 2]);
        $this->changeCritereCoefAndType('La salle de bain est abîmée ou inexistante.', coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 2]); // TODO coef
        $this->changeCritereCoefAndType("J'ai un problème avec l’eau potable.", coef: 4, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 1]); // TODO deux types possibles, coef cahnge suivant type et criticité
        $this->changeCritereCoefAndType('Il y a des nuisibles dans mon logement (blattes, punaises de lit, rongeurs...).', coef: 0, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0, 0, 0]);
        $this->changeCritereCoefAndType("J’ai un problème d'évacuation des eaux usées.", coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 3]); // TODO deux types possible
        $this->changeCritereCoefAndType('Les installations électriques ne sont pas en bon état.', coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 1, 1], criticiteScores: [0, 1, 2]);
        $this->changeCritereCoefAndType('Le chauffage ne fonctionne pas bien.', coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [1, 2, 1]); // TODO deux types ou pas ?
        $this->changeCritereCoefAndType("J'ai un problème de ventilation dans mon logement.", coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [1, 2, 3]); // TODO deux types ou pas ?
        $this->changeCritereCoefAndType('De l’air s’infiltre dans mon logement.', coef: 3, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 1]); // TODO : coef et type
        $this->changeCritereCoefAndType('Mes factures de chauffage sont anormalement élevées.', coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [1, 1, 1]);
        $this->changeCritereCoefAndType('Mon logement est mal isolé.', coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [1, 1, 1]);
        $this->changeCritereCoefAndType("J’utilise un appareil d'appoint pour le chauffage.", coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 1]);
        $this->changeCritereCoefAndType("L'installation de gaz n’est pas en bon état", coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 1, 1], criticiteScores: [0, 1, 3]);
        $this->changeCritereCoefAndType('Le sol est abîmé.', coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [0.5, 0.5, 2]); // TODO deux types possible
        $this->changeCritereCoefAndType('Les escaliers sont abîmés.', coef: 2, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [1, 2, 3]);
        $this->changeCritereCoefAndType('Les murs et plafonds intérieurs sont mal entretenus', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 2]);
        $this->changeCritereCoefAndType('Les façades ne sont pas en bon état.', coef: 2, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 1], criticiteScores: [0.5, 1, 2]); // TODO plusieurs coefs
        $this->changeCritereCoefAndType('La toiture n’est pas en bon état.', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [1, 2, 3]);
        $this->changeCritereCoefAndType('Mon logement est en sous-sol.', coef: 8, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0], criticiteScores: [2]);
        $this->changeCritereCoefAndType('Mon logement est sous les combles.', coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 2]);
        $this->changeCritereCoefAndType("Il n'y a pas de fenêtre dans mon salon ou ma salle à manger.", coef: 8, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0], criticiteScores: [0.5]);
        $this->changeCritereCoefAndType('Mon logement est une pièce unique, de moins de 9m².', coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0], criticiteScores: [3]);
        $this->changeCritereCoefAndType('Mon salon ou ma salle à manger mesure moins de 7m².', coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0], criticiteScores: [1]);
        $this->changeCritereCoefAndType('Les plafonds sont trop bas (moins de 2.20 m).', coef: 2, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [1], criticiteScores: [2]);
        $this->changeCritereCoefAndType('La lumière naturelle en pleine journée est insuffisante.', coef: 8, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0, 0.25, 0.5]);
        $this->changeCritereCoefAndType('Les déchets sont mal stockés.', coef: 1, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0, 1, 3]);
        $this->changeCritereCoefAndType('L’usage des lieux n’est pas respecté.', coef: 0, type: Critere::TYPE_BATIMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0, 0, 1]); // TODO plusieurs coefs
        $this->changeCritereCoefAndType('Le bruit à l’intérieur du logement ou du bâtiment est gênant.', coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 1, 2]);
        $this->changeCritereCoefAndType('Le bruit extérieur est gênant.', coef: 1, type: Critere::TYPE_LOGEMENT, criticiteIsDanger: [0, 0, 0], criticiteScores: [0.5, 0.5, 1]);

        // $this->changeCritereCoefAndType("J'ai un problème avec l’eau potable.");
        // $this->changeCritereCoefAndType("Nuisances de l'environnement");
        // $this->changeCritereCoefAndType("Murs extérieurs");
        // $this->changeCritereCoefAndType("Charpentes");
        // $this->changeCritereCoefAndType("Toitures");
        // $this->changeCritereCoefAndType("Prévention des chutes");
        // $this->changeCritereCoefAndType("Réseau eau potable");
        // $this->changeCritereCoefAndType("Évacuation des eaux usées et raccordement");
        // $this->changeCritereCoefAndType("Propreté");
        // $this->changeCritereCoefAndType("Présence d'animaux nuisibles ");
        // $this->changeCritereCoefAndType("Éclairement naturel des pièces principales");
        // $this->changeCritereCoefAndType("Organisation intérieure du logement");
        // $this->changeCritereCoefAndType("Dimension des pièces / surface habitable");
        // $this->changeCritereCoefAndType("Protection phonique / bruits extérieurs");
        // $this->changeCritereCoefAndType("Protection phonique / bruits intérieurs");
        // $this->changeCritereCoefAndType("Isolation thermique");
        // $this->changeCritereCoefAndType("Chaudière gaz:\r\nInstallation, sécurité ");
        // $this->changeCritereCoefAndType("Évacuation des produits de combustion");
        // $this->changeCritereCoefAndType("Toxiques, peintures au plomb");
        // $this->changeCritereCoefAndType("Risque amiante");
        // $this->changeCritereCoefAndType("Prévention des chutes de personnes");
        // $this->changeCritereCoefAndType("Appréciation globale des manifestations d'humidité");
        // $this->changeCritereCoefAndType("Réseau d'alimentation en eau potable");
        // $this->changeCritereCoefAndType("Réseau d'évacuation des eaux usées");
        // $this->changeCritereCoefAndType("Réseau d'électricité");
        // $this->changeCritereCoefAndType("Fils électriques dénudés");
        // $this->changeCritereCoefAndType("Toilettes");
        // $this->changeCritereCoefAndType("Salle de bain ou salle d'eau");
        // $this->changeCritereCoefAndType("Entretien des lieux, propreté courante");
        // $this->changeCritereCoefAndType("Sur-occupation");

        $this->changeCritereLabel('L’installation du réseau électrique a un problème.', 'L’installation du réseau électrique du bâtiment a un problème');
        $this->changeCritereLabel('Fils électriques dénudés', 'Fils électriques dénudés -Bâtiment/Parties communes');

        return Command::SUCCESS;
    }

    private function changeCritereCoefAndType(string $label, int $coef, int $type, ?array $criticiteIsDanger, ?array $criticiteScores)
    {
        $critere = $this->critereRepository->findByLabel($label);
        if ($critere) {
            $this->io->text('Update critere with label : '.$critere->getLabel());
            $critere->setNewCoef($coef);
            $critere->setType($type);
            $this->io->text('New Coef : '.$critere->getNewCoef().'  and type '.$critere->getType().'  and is_danger '.$critere->getIsDanger());

            if ($criticiteIsDanger || $criticiteScores) {
                $this->changeCriticiteScore($critere, $criticiteIsDanger, $criticiteScores);
            }

            $this->entityManager->persist($critere);
            $this->entityManager->flush();
        } else {
            $this->io->text('No critere with label : '.$label);
        }
    }

    private function changeCriticiteScore(Critere $critere, ?array $criticiteIsDanger, ?array $criticiteScores)
    {
        $criticites = $this->criticiteRepository->findBy(
            ['critere' => $critere],
            ['score' => 'ASC'] // TODO id plutôt ?
        );

        if ($criticites) {
            $count = 0;
            foreach ($criticites as $criticite) {
                $this->io->text('Update criticite with label : '.$criticite->getLabel());
                $criticite->setIsDanger($criticiteIsDanger[$count]);
                $criticite->setNewScore($criticiteScores[$count]);
                $this->io->text('IsDanger  : '.$criticite->getIsDanger().'New score : '.$criticite->getNewScore());
                $this->entityManager->persist($criticite);
                ++$count;
            }

            $this->entityManager->flush();
        } else {
            $this->io->text('No criticites associated with critere with label : '.$critere->getLabel());
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
            $this->io->text('No critere with label : '.$oldLabel);
        }
    }
}
