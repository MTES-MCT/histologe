<?php

namespace App\Command;

use App\Entity\Enum\SuiviCategory;
use App\Entity\Suivi;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerInterface;

#[AsCommand(
    name: 'app:init-suivi-category-command',
    description: 'Initialize SuiviCategory for existing suivis',
)]
class InitSuiviCategoryCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        #[Autowire(service: 'html_sanitizer.sanitizer.app.message_sanitizer')]
        private readonly HtmlSanitizerInterface $htmlSanitizer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $connection = $this->entityManager->getConnection();

        // ASK_DOCUMENT
        $askDocumentIntro = 'Bonjour,<br><br>';
        $askDocumentIntro .= 'Vous avez signalé un problème sur un logement.<br>';
        $askDocumentIntro .= 'Votre dossier a bien été enregistré par nos services.<br><br>';
        $askDocumentIntro .= 'Afin de nous aider à traiter au mieux votre dossier, veuillez nous fournir :<br>';
        $askDocumentIntro = $this->htmlSanitizer->sanitize($askDocumentIntro);
        $sql = 'UPDATE suivi SET category = :category WHERE description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::ASK_DOCUMENT->name, 'description' => $askDocumentIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie ASK_DOCUMENT', $rowCount));

        // ASK_FEEDBACK_SENT
        $askFeedbackSentDescription = 'Un message automatique a été envoyé à l\'usager pour lui demander de mettre à jour sa situation.';
        $askFeedbackSentDescription = $this->htmlSanitizer->sanitize($askFeedbackSentDescription);
        $sql = 'UPDATE suivi SET category = :category WHERE description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::ASK_FEEDBACK_SENT->name, 'description' => $askFeedbackSentDescription]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie ASK_FEEDBACK_SENT', $rowCount));

        // SIGNALEMENT_IS_ACTIVE
        $signalementIsActiveDescription = 'Signalement validé';
        $sql = 'UPDATE suivi SET category = :category WHERE description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::SIGNALEMENT_IS_ACTIVE->name, 'description' => $signalementIsActiveDescription]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_IS_ACTIVE', $rowCount));

        // AFFECTATION_IS_ACCEPTED
        $affectationIsAcceptedDescription = ' ';
        $affectationIsAcceptedDescription .= '<p>Suite à votre signalement, le ou les partenaires compétents sur votre dossier ont été informés et ont validé  la prise en charge de votre dossier.';
        $affectationIsAcceptedDescription .= '<br>Vous serez bientôt contacté(e) pour des informations complémentaires  ou pour programmer une visite du logement.</p> ';
        $affectationIsAcceptedDescription .= '<p>N’hésitez pas à partager toute information qui vous semblerait pertinente.  ';
        $affectationIsAcceptedDescription .= 'Nous reviendrons vers vous également afin de nous assurer de l’avancée des démarches.</p>';
        $affectationIsAcceptedDescription = $this->htmlSanitizer->sanitize($affectationIsAcceptedDescription);
        $sql = 'UPDATE suivi SET category = :category WHERE description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::AFFECTATION_IS_ACCEPTED->name, 'description' => $affectationIsAcceptedDescription]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie AFFECTATION_IS_ACCEPTED', $rowCount));

        // INTERVENTION_IS_PLANNED
        $interventionIsPlannedIntro = 'Visite programmée : une visite du logement situé ';
        $sql = 'UPDATE suivi SET category = :category WHERE context = :context AND description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_IS_PLANNED->name, 'description' => $interventionIsPlannedIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_PLANNED', $rowCount));

        // INTERVENTION_IS_CANCELED
        $interventionIsCanceledIntro = 'Annulation de visite : la visite du logement prévue le ';
        $sql = 'UPDATE suivi SET category = :category WHERE context = :context AND description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_IS_CANCELED->name, 'description' => $interventionIsCanceledIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_CANCELED', $rowCount));

        // INTERVENTION_HAS_CONCLUSION
        $interventionHasConclusionIntro = 'Après visite du logement';
        $sql = 'UPDATE suivi SET category = :category WHERE context = :context AND description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_HAS_CONCLUSION->name, 'description' => $interventionHasConclusionIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_HAS_CONCLUSION', $rowCount));

        // INTERVENTION_IS_RESCHEDULED
        $interventionIsRescheduledIntro = 'Changement de date de visite : la visite du logement initialement prévue le ';
        $sql = 'UPDATE suivi SET category = :category WHERE context = :context AND description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_IS_RESCHEDULED->name, 'description' => $interventionIsRescheduledIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_RESCHEDULED', $rowCount));

        // SIGNALEMENT_STATUS_IS_SYNCHRO
        $signalementStatusIsSynchroIntro = [
            'Signalement <b>remis en attente via ',
            'Signalement <b>accepté via ',
            'Signalement <b>cloturé via ',
            'Signalement <b>refusé via ',
            'Le signalement a été accepté par IDOSS',
            'Le signalement a été clôturé par IDOSS avec le motif suivant : ',
            'Le signalement a été mis à jour ("',
        ];
        foreach ($signalementStatusIsSynchroIntro as $intro) {
            $sql = 'UPDATE suivi SET category = :category WHERE description LIKE :description';
            $stmt = $connection->prepare($sql);
            $result = $stmt->executeQuery(['category' => SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO->name, 'description' => $intro.'%']);
            $rowCount = $result->rowCount();
            $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_STATUS_IS_SYNCHRO ('.$intro.')', $rowCount));
        }

        // NEW_DOCUMENT
        $descriptionNewDocumentContains = '<ul><li><a class="fr-link" target="_blank"';
        $sql = 'UPDATE suivi SET category = :category WHERE category IS NULL AND description LIKE :description';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::NEW_DOCUMENT->name, 'description' => '%'.$descriptionNewDocumentContains.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie NEW_DOCUMENT', $rowCount));

        // MESSAGE_USAGER
        $sql = 'UPDATE suivi SET category = :category WHERE type IN ('.Suivi::TYPE_USAGER.','.Suivi::TYPE_USAGER_POST_CLOTURE.')';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::MESSAGE_USAGER->name]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie MESSAGE_USAGER', $rowCount));

        return Command::SUCCESS;
    }
}
