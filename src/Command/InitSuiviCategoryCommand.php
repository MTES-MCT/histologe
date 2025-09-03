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
    public const string UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE = 'UPDATE suivi SET category = :category WHERE description LIKE :description AND category IS NULL';
    public const string UPDATE_SUIVI_CATEGORY_WHERE_CONTEXT_AND_DESCRIPTION_LIKE = 'UPDATE suivi SET category = :category WHERE context = :context AND description LIKE :description AND category IS NULL';

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

        // MESSAGE_USAGER
        $sql = 'UPDATE suivi SET category = :category WHERE category IS NULL AND type = '.Suivi::TYPE_USAGER;
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::MESSAGE_USAGER->name]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie MESSAGE_USAGER', $rowCount));

        // MESSAGE_USAGER_POST_CLOTURE
        $sql = 'UPDATE suivi SET category = :category WHERE category IS NULL AND type = '.Suivi::TYPE_USAGER_POST_CLOTURE;
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::MESSAGE_USAGER_POST_CLOTURE->name]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie MESSAGE_USAGER_POST_CLOTURE', $rowCount));

        // ASK_DOCUMENT
        $askDocumentIntro = 'Bonjour,<br><br>';
        $askDocumentIntro .= 'Vous avez signalé un problème sur un logement.<br>';
        $askDocumentIntro .= 'Votre dossier a bien été enregistré par nos services.<br><br>';
        $askDocumentIntro .= 'Afin de nous aider à traiter au mieux votre dossier, veuillez nous fournir :<br>';
        $askDocumentIntro = $this->htmlSanitizer->sanitize($askDocumentIntro);
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::ASK_DOCUMENT->name, 'description' => $askDocumentIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie ASK_DOCUMENT', $rowCount));

        $askDocumentIntro = 'Bonjour,<br><br>';
        $askDocumentIntro .= 'Vous avez un signalé un problème sur un logement.<br>';
        $askDocumentIntro .= 'Votre dossier a bien été enregistré par nos services.<br><br>';
        $askDocumentIntro .= 'Afin de nous aider à traiter au mieux votre dossier, veuillez nous fournir :<br>';
        $askDocumentIntro = $this->htmlSanitizer->sanitize($askDocumentIntro);
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::ASK_DOCUMENT->name, 'description' => $askDocumentIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie ASK_DOCUMENT (v typo)', $rowCount));

        // ASK_FEEDBACK_SENT
        $askFeedbackSentDescription = 'Un message automatique a été envoyé à l\'usager pour lui demander de mettre à jour sa situation.';
        $askFeedbackSentDescription = $this->htmlSanitizer->sanitize($askFeedbackSentDescription);
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::ASK_FEEDBACK_SENT->name, 'description' => $askFeedbackSentDescription]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie ASK_FEEDBACK_SENT', $rowCount));

        // SIGNALEMENT_IS_REFUSED
        $signalementIsRefusedDescriptionIntro = 'Signalement cloturé car non-valide avec le motif suivant :';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::SIGNALEMENT_IS_REFUSED->name, 'description' => $signalementIsRefusedDescriptionIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_IS_REFUSED', $rowCount));

        // SIGNALEMENT_IS_ACTIVE
        $signalementIsActiveDescription = 'Signalement validé';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::SIGNALEMENT_IS_ACTIVE->name, 'description' => $signalementIsActiveDescription]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_IS_ACTIVE', $rowCount));

        // SIGNALEMENT_IS_CLOSED
        $signalementIsClosedDescriptionIntro = 'Le signalement a été cloturé pour ';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_CONTEXT_AND_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::SIGNALEMENT_IS_CLOSED->name, 'description' => $signalementIsClosedDescriptionIntro.'%', 'context' => Suivi::CONTEXT_SIGNALEMENT_CLOSED]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_IS_CLOSED', $rowCount));

        // SIGNALEMENT_IS_REOPENED
        $signalementIsReopenedDescriptionIntro = 'Signalement rouvert pour ';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::SIGNALEMENT_IS_REOPENED->name, 'description' => $signalementIsReopenedDescriptionIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_IS_REOPENED', $rowCount));

        // SIGNALEMENT_EDITED_BO
        $signalementEditedBoIntro = [
            'Modification du signalement par un partenaire',
            'L\'adresse du logement a été modifiée par ',
            'Les coordonnées du tiers déclarant ont été modifiées par ',
            'Les coordonnées du foyer ont été modifiées par ',
            'Les coordonnées du bailleur ont été modifiées par ',
            'Les coordonnées de l\'agence ont été modifiées par ',
            'Les informations sur le logement ont été modifiées par ',
            'La description du logement a été modifiée par ',
            'La description du logement a été modifée par ',
            'La situation du foyer a été modifiée par ',
            'Les procédures et démarches ont été modifiées par ',
            'La composition du logement a été modifée par ',
        ];
        foreach ($signalementEditedBoIntro as $intro) {
            $intro = $this->htmlSanitizer->sanitize($intro);
            $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
            $result = $stmt->executeQuery(['category' => SuiviCategory::SIGNALEMENT_EDITED_BO->name, 'description' => $intro.'%']);
            $rowCount = $result->rowCount();
            $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_EDITED_BO ('.$intro.')', $rowCount));
        }

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
            $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
            $result = $stmt->executeQuery(['category' => SuiviCategory::SIGNALEMENT_STATUS_IS_SYNCHRO->name, 'description' => $intro.'%']);
            $rowCount = $result->rowCount();
            $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_STATUS_IS_SYNCHRO ('.$intro.')', $rowCount));
        }

        // AFFECTATION_IS_ACCEPTED
        $affectationIsAcceptedDescription = ' ';
        $affectationIsAcceptedDescription .= '<p>Suite à votre signalement, le ou les partenaires compétents sur votre dossier ont été informés et ont validé  la prise en charge de votre dossier.';
        $affectationIsAcceptedDescription .= '<br>Vous serez bientôt contacté(e) pour des informations complémentaires  ou pour programmer une visite du logement.</p> ';
        $affectationIsAcceptedDescription .= '<p>N’hésitez pas à partager toute information qui vous semblerait pertinente.  ';
        $affectationIsAcceptedDescription .= 'Nous reviendrons vers vous également afin de nous assurer de l’avancée des démarches.</p>';
        $affectationIsAcceptedDescription = $this->htmlSanitizer->sanitize($affectationIsAcceptedDescription);
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::AFFECTATION_IS_ACCEPTED->name, 'description' => $affectationIsAcceptedDescription]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie AFFECTATION_IS_ACCEPTED', $rowCount));

        $affectationIsAcceptedDescription = 'Le signalement a été accepté';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::AFFECTATION_IS_ACCEPTED->name, 'description' => $affectationIsAcceptedDescription]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie AFFECTATION_IS_ACCEPTED (v1)', $rowCount));

        // AFFECTATION_IS_REFUSED
        $affectationIsRefusedDescriptionIntro = 'Le signalement a été refusé avec le motif suivant';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::AFFECTATION_IS_REFUSED->name, 'description' => $affectationIsRefusedDescriptionIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie AFFECTATION_IS_REFUSED', $rowCount));

        // AFFECTATION_IS_CLOSED
        $affectationIsClosedDescriptionIntro = 'Le signalement a été cloturé pour ';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::AFFECTATION_IS_CLOSED->name, 'description' => $affectationIsClosedDescriptionIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie AFFECTATION_IS_CLOSED', $rowCount));

        // SIGNALEMENT_IS_CLOSED
        $sql = "UPDATE suivi SET category = 'SIGNALEMENT_IS_CLOSED' WHERE description like 'Le signalement a été cloturé pour tous les partenaires%' and category = 'AFFECTATION_IS_CLOSED'";
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery([]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie SIGNALEMENT_IS_CLOSED', $rowCount));

        // INTERVENTION_IS_REQUIRED
        $interventionIsRequiredIntro = 'La réalisation d\'une visite est nécessaire pour caractériser les désordres signalés.';
        $interventionIsRequiredIntro = $this->htmlSanitizer->sanitize($interventionIsRequiredIntro);
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_IS_REQUIRED->name, 'description' => $interventionIsRequiredIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_REQUIRED', $rowCount));

        // INTERVENTION_IS_CANCELED
        $interventionIsCanceledIntro = 'Annulation de visite : la visite du logement prévue le ';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_CONTEXT_AND_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_IS_CANCELED->name, 'description' => $interventionIsCanceledIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_CANCELED', $rowCount));

        // INTERVENTION_IS_ABORTED
        $interventionIsAbortedIntro = 'La visite du logement prévue le ';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_CONTEXT_AND_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_IS_ABORTED->name, 'description' => $interventionIsAbortedIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_ABORTED', $rowCount));

        // INTERVENTION_HAS_CONCLUSION
        $interventionHasConclusionIntro = 'Après visite du logement';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_CONTEXT_AND_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_HAS_CONCLUSION->name, 'description' => $interventionHasConclusionIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_HAS_CONCLUSION', $rowCount));

        // INTERVENTION_HAS_CONCLUSION_EDITED
        $interventionHasConclusionEditedIntro = 'Edition de la conclusion de la visite par ';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_CONTEXT_AND_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_HAS_CONCLUSION_EDITED->name, 'description' => $interventionHasConclusionEditedIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_HAS_CONCLUSION_EDITED', $rowCount));

        // INTERVENTION_IS_RESCHEDULED
        $interventionIsRescheduledIntro = [
            'Changement de date de visite : la visite du logement initialement prévue le ',
            'La date de visite dans ',
            'La date de visite de contrôle dans ',
            'La date de arrêté préfectoral dans ',
        ];
        foreach ($interventionIsRescheduledIntro as $intro) {
            $intro = $this->htmlSanitizer->sanitize($intro);
            $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_CONTEXT_AND_DESCRIPTION_LIKE);
            $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_IS_RESCHEDULED->name, 'description' => $intro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
            $rowCount = $result->rowCount();
            $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_RESCHEDULED ('.$intro.')', $rowCount));
        }

        // INTERVENTION_PLANNED_REMINDER
        $interventionReminderIntro = '<strong>Rappel de visite :</strong> la visite du logement situé';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_CONTEXT_AND_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_PLANNED_REMINDER->name, 'description' => $interventionReminderIntro.'%', 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_PLANNED_REMINDER', $rowCount));

        // INTERVENTION_IS_CREATED
        $sql = 'UPDATE suivi SET category = :category WHERE context = :context AND category IS NULL';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::INTERVENTION_IS_CREATED->name, 'context' => Suivi::CONTEXT_INTERVENTION]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_CREATED', $rowCount));

        // INTERVENTION_IS_REQUIRED
        $sql = "UPDATE suivi SET category = 'INTERVENTION_IS_REQUIRED' WHERE category = 'INTERVENTION_IS_CREATED' AND description LIKE '%renseignée pour le logement. Merci de programmer une visite dès que possible !'";
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery([]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie INTERVENTION_IS_REQUIRED', $rowCount));

        // DOCUMENT_DELETED_BY_USAGER
        $descriptionDocumentDeletedByUsagerIntro = 'Document supprimé par l&#039;usager :<ul><li>';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::DOCUMENT_DELETED_BY_USAGER->name, 'description' => $descriptionDocumentDeletedByUsagerIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie DOCUMENT_DELETED_BY_USAGER (document)', $rowCount));

        $descriptionPhotoDeletedByUsagerIntro = 'Photo supprimée par l&#039;usager :<ul><li>';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::DOCUMENT_DELETED_BY_USAGER->name, 'description' => $descriptionPhotoDeletedByUsagerIntro.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie DOCUMENT_DELETED_BY_USAGER (photo)', $rowCount));

        // DOCUMENT_DELETED_BY_PARTNER
        $descriptionDocumentDeletedByPartnerContains = ' a supprimé le document suivant :<ul>';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::DOCUMENT_DELETED_BY_PARTNER->name, 'description' => '%'.$descriptionDocumentDeletedByPartnerContains.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie DOCUMENT_DELETED_BY_PARTNER (document)', $rowCount));

        $descriptionPhotoDeletedByPartnerContains = ' a supprimé la photo suivante :<ul>';
        $stmt = $connection->prepare(self::UPDATE_SUIVI_CATEGORY_WHERE_DESCRIPTION_LIKE);
        $result = $stmt->executeQuery(['category' => SuiviCategory::DOCUMENT_DELETED_BY_PARTNER->name, 'description' => '%'.$descriptionPhotoDeletedByPartnerContains.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie DOCUMENT_DELETED_BY_PARTNER (photo)', $rowCount));

        // NEW_DOCUMENT
        $descriptionNewDocumentContains = '<ul><li><a class="fr-link" target="_blank"';
        $sql = 'UPDATE suivi SET category = :category WHERE type = :type AND description LIKE :description AND category IS NULL';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::NEW_DOCUMENT->name, 'type' => Suivi::TYPE_AUTO, 'description' => '%'.$descriptionNewDocumentContains.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie NEW_DOCUMENT', $rowCount));

        // MESSAGE_PARTNER
        $sql = 'UPDATE suivi SET category = :category WHERE type = :type AND category IS NULL';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::MESSAGE_PARTNER->name, 'type' => Suivi::TYPE_PARTNER]);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie MESSAGE_PARTNER', $rowCount));
        $this->entityManager->flush();

        // DEMANDE_ABANDON_PROCEDURE
        $demandeAbandonProcedureDescription = 'a demandé l&#039;arrêt de la procédure';
        $sql = 'UPDATE suivi SET category = :category WHERE description LIKE :description AND type IN ('.Suivi::TYPE_USAGER.','.Suivi::TYPE_USAGER_POST_CLOTURE.')';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::DEMANDE_ABANDON_PROCEDURE->name, 'description' => '%'.$demandeAbandonProcedureDescription.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie DEMANDE_ABANDON_PROCEDURE', $rowCount));

        // DEMANDE_POURSUITE_PROCEDURE
        $demandePoursuiteProcedureDescription = 'a indiqué vouloir poursuivre la procédure';
        $sql = 'UPDATE suivi SET category = :category WHERE description LIKE :description AND type IN ('.Suivi::TYPE_USAGER.','.Suivi::TYPE_USAGER_POST_CLOTURE.')';
        $stmt = $connection->prepare($sql);
        $result = $stmt->executeQuery(['category' => SuiviCategory::DEMANDE_POURSUITE_PROCEDURE->name, 'description' => '%'.$demandePoursuiteProcedureDescription.'%']);
        $rowCount = $result->rowCount();
        $io->success(sprintf('%d lignes ont été mises à jour avec la catégorie DEMANDE_POURSUITE_PROCEDURE', $rowCount));

        return Command::SUCCESS;
    }
}
