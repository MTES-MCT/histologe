<?php

namespace App\Service\Signalement;

use App\Entity\Model\InformationComplementaire;
use App\Entity\Model\TypeCompositionLogement;
use App\Entity\Signalement;
use App\Entity\SignalementDraft;
use App\Entity\User;

class Anonymizer
{
    public function __construct(
    ) {
    }

    public function anonymize(Signalement $signalement): void
    {
        $this->anonymizeUsers($signalement);
        $this->anonymizeSignalement($signalement);
        if ($signalement->getCreatedFrom()) {
            $this->anonymizeSignalementDraft($signalement->getCreatedFrom());
        }
        $this->anonymizeSuivis($signalement);
        $this->anonymizeVisites($signalement);
        $this->anonymizeFailedEmails($signalement);
        $this->deleteInvitationsTiers($signalement);
        $this->deleteFiles($signalement);
        $this->deleteHistory($signalement);
        $this->deleteBrevoContacts($signalement);
    }

    private function anonymizeUsers(Signalement $signalement): void
    {
        /*anonymiser les utilisateurs liés à ce signalement (déclarant, occupant, propriétaire)
        */
        $users = $signalement->getUsagers();
        /** @var User $user */
        foreach ($users as $user) {
            $user->anonymize();
        }
    }

    private function anonymizeSignalement(Signalement $signalement): void
    {
        $informationComplementaire = new InformationComplementaire();
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire = clone $signalement->getInformationComplementaire();
        }
        $typeCompositionLogement = new TypeCompositionLogement();
        if (!empty($signalement->getTypeCompositionLogement())) {
            $typeCompositionLogement = clone $signalement->getTypeCompositionLogement();
        }
        /*
        occupant : GARDER :  adresse / geoloc
        */
        // SUPPRIMER : prénom / nom / mail
        if ($signalement->getSignalementUsager()?->getOccupant()) {
            $signalement
                ->setPrenomOccupant($signalement->getSignalementUsager()?->getOccupant()->getPrenom())
                ->setNomOccupant($signalement->getSignalementUsager()?->getOccupant()->getNom())
                ->setMailOccupant($signalement->getSignalementUsager()?->getOccupant()->getEmail());
        } else {
            $signalement
                ->setPrenomOccupant(User::ANONYMIZED_PRENOM)
                ->setNomOccupant(User::ANONYMIZED_NOM)
                ->setMailOccupant(User::ANONYMIZED_MAIL.date('YmdHis').'.'.uniqid());
        }
        // SUPPRIMER : téléphone + bis / date de naissance / civilité
        $signalement
            ->setTelOccupant(null)
            ->setTelOccupantBis(null)
            ->setDateNaissanceOccupant(null)
            ->setCiviliteOccupant(null);
        // SUPPRIMER : numero allocataire / montant allocation / montant loyer / mail_occupant_temp / autre situation de vulnérabilité
        $signalement
            ->setIsAllocataire(null)
            ->setNumAllocataire(null)
            ->setMontantAllocation(null)
            ->setLoyer(null)
            ->setMailOccupantTemp(null)
            ->setAutreSituationVulnerabilite(null);
        // SUPPRIMER : nombre d'habitants (adultes / enfants)
        $signalement
            ->setNbAdultes(null)
            ->setNbEnfantsM6(null)
            ->setNbEnfantsP6(null);
        if (!empty($signalement->getTypeCompositionLogement())) {
            $typeCompositionLogement
                ->setCompositionLogementEnfants(null)
                ->setCompositionLogementNombreEnfants(null);
        }
        // SUPPRIMER : revenu fiscal / date emmenagement / bénéficiaire rsa / bénéficaire fsl
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire
                ->setInformationsComplementairesSituationOccupantsBeneficiaireRsa(null)
                ->setInformationsComplementairesSituationOccupantsBeneficiaireFsl(null)
                ->setInformationsComplementairesSituationOccupantsTypeAllocation(null)
                ->setInformationsComplementairesSituationOccupantsRevenuFiscal(null)
                ->setInformationsComplementairesSituationOccupantsDateNaissance(null)
                ->setInformationsComplementairesSituationOccupantsDemandeRelogement(null)
                ->setInformationsComplementairesSituationOccupantsDateEmmenagement(null)
                ->setInformationsComplementairesSituationOccupantsLoyersPayes(null);
        }

        /*
        déclarant : SUPPRIMER : prénom / nom / mail / téléphone + bis / matricule
        */
        if ($signalement->getSignalementUsager()?->getDeclarant()) {
            $signalement
                ->setPrenomDeclarant($signalement->getSignalementUsager()?->getDeclarant()->getPrenom())
                ->setNomDeclarant($signalement->getSignalementUsager()?->getDeclarant()->getNom())
                ->setMailDeclarant($signalement->getSignalementUsager()?->getDeclarant()->getEmail());
        } else {
            $signalement
                ->setPrenomDeclarant(User::ANONYMIZED_PRENOM)
                ->setNomDeclarant(User::ANONYMIZED_NOM)
                ->setMailDeclarant(User::ANONYMIZED_MAIL.date('YmdHis').'.'.uniqid());
        }
        $signalement
            ->setTelDeclarant(null)
            ->setTelDeclarantSecondaire(null)
            ->setMatriculeDeclarant(null);

        /*
        proprio : GARDER dénomination / nom / adresse ?
        */
        // SUPPRIMER prénom / mail / téléphone + bis
        $signalement
            ->setPrenomProprio(null)
            ->setMailProprio(null)
            ->setTelProprio(null)
            ->setTelProprioSecondaire(null);
        //SUPPRIMER : date de naissance / bénéficiaire rsa / bénéficaire fsl / revenu fiscal
        if (!empty($signalement->getInformationComplementaire())) {
            $informationComplementaire
                ->setInformationsComplementairesSituationBailleurDateEffetBail(null)
                ->setInformationsComplementairesSituationBailleurBeneficiaireRsa(null)
                ->setInformationsComplementairesSituationBailleurBeneficiaireFsl(null)
                ->setInformationsComplementairesSituationBailleurRevenuFiscal(null)
                ->setInformationsComplementairesSituationBailleurDateNaissance(null);
        }
        /*
        autre : champs détails / réponse assurance / com cloture / raison refus intervention
        */
        /*
        login bailleur / code suivi ?
        */
        $signalement->setInformationComplementaire($informationComplementaire);
        $signalement->setTypeCompositionLogement($typeCompositionLogement);
    }

    private function anonymizeSignalementDraft(SignalementDraft $signalementDraft): void
    {
        /*
    occupant : prénom / nom / mail / téléphone + bis / date de naissance / civilité / numero allocataire / montant allocation / montant loyer / revenu fiscal / date emmenagement / bénéficiaire rsa / bénéficaire fsl / mail_occupant_temp / paiement des loyers à jour / nombre de personnes / enfants / autre situation de vulnérabilité
        garder adresse / geoloc
    déclarant : prénom / nom / mail / téléphone + bis / matricule
    proprio : prénom / mail / téléphone + bis / date de naissance / bénéficiaire rsa / bénéficaire fsl / revenu fiscal
        garder dénomination / nom / adresse ?
    autre : champs détails / réponse assurance / com cloture / raison refus intervention
        */
    }

    private function anonymizeSuivis(Signalement $signalement): void
    {
        /*anonymiser les suivis rédigés
        */
    }

    private function anonymizeVisites(Signalement $signalement): void
    {
        /*anonymiser les visites liées à ce signalement
        */
    }

    private function anonymizeFailedEmails(Signalement $signalement): void
    {
        /*anonymiser les failed emails liés à ces adresses mails
        */
    }

    private function deleteInvitationsTiers(Signalement $signalement): void
    {
        /*supprimer les invitations tiers liées à ce signalement
        */
    }

    private function deleteFiles(Signalement $signalement): void
    {
        /*supprimer physiquement les fichiers liés à ce signalement
        */
    }

    private function deleteHistory(Signalement $signalement): void
    {
        /*supprimer l'historique de modifications du signalement
        */
    }

    private function deleteBrevoContacts(Signalement $signalement): void
    {
        /*supprimer les contacts Brevo liés à ce signalement
        */
    }
}
