<?php

namespace App\Factory;

use App\Entity\Enum\MotifRefus;
use App\Entity\Signalement;
use App\Entity\Suivi;
use App\Entity\User;
use App\Service\Sanitizer;

class SuiviFactory
{
    public function createInstanceFrom(
        ?User $user,
        Signalement $signalement,
        array $params = [],
        bool $isPublic = false,
        string $context = ''
    ): Suivi {
        return (new Suivi())
            ->setCreatedBy($user)
            ->setSignalement($signalement)
            ->setDescription($this->buildDescription($params))
            ->setType($this->buildType($user, $params))
            ->setIsPublic($isPublic)
            ->setContext($context);
    }

    private function buildType(?User $user, array $params): int
    {
        if (isset($params['type'])) {
            return (int) $params['type'];
        }

        if ($user && \in_array('ROLE_USAGER', $user->getRoles())) {
            return SUIVI::TYPE_USAGER;
        }

        if (isset($params['accept'])
        || isset($params['suivi'])
        || (isset($params['domain']) && 'esabora' === $params['domain'])) {
            return SUIVI::TYPE_AUTO;
        }

        return SUIVI::TYPE_PARTNER;
    }

    private function buildDescription($params): string
    {
        $description = '';
        if (empty($params)) {
            return $description;
        }

        if (isset($params['motif_cloture'])) {
            return $this->buildDescriptionClotureSignalement($params);
        }

        if (isset($params['accept']) || isset($params['suivi'])) {
            return $this->buildDescriptionAnswerAffectation($params);
        }

        if (isset($params['domain']) && 'esabora' === $params['domain']) {
            return 'Signalement <b>'.$params['description'].'</b> par '.$params['name_partner'];
        }

        if (isset($params['description'])) {
            return $params['description'];
        }

        return $description;
    }

    private function buildDescriptionClotureSignalement($params): string
    {
        $motifSuivi = Sanitizer::sanitize($params['motif_suivi']);

        return sprintf(
            'Le signalement a été cloturé pour %s avec le motif suivant <br><strong>%s</strong><br><strong>Desc. : </strong>%s',
            $params['subject'],
            $params['motif_cloture']->label(),
            $motifSuivi
        );
    }

    private function buildDescriptionAnswerAffectation($params): string
    {
        $description = '';
        if (isset($params['accept'])) {
            $description = 'Le signalement a été accepté';
        } elseif (isset($params['suivi'])) {
            $motifRejected = !empty($params['motifRefus']) ? MotifRefus::tryFrom($params['motifRefus'])->label() : 'Non précisé';
            $commentaire = Sanitizer::sanitize($params['suivi']);
            $description = 'Le signalement a été refusé avec le motif suivant : '.$motifRejected.'.<br>Plus précisément :<br>'.$commentaire;
        }

        return $description;
    }
}
