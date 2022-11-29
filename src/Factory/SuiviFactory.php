<?php

namespace App\Factory;

use App\Entity\Suivi;
use App\Service\Sanitizer;
use Symfony\Component\Security\Core\Security;

class SuiviFactory
{
    public function __construct(private Security $security)
    {
    }

    public function createInstanceFrom(array $params = [], bool $isPublic = false): Suivi
    {
        $suivi = (new Suivi())
            ->setCreatedBy($this->security->getUser())
            ->setDescription($this->buildDescription($params))
            ->setIsPublic($isPublic);

        return $suivi;
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

        return $description;
    }

    private function buildDescriptionClotureSignalement($params): string
    {
        $motifSuivi = Sanitizer::sanitize($params['motif_suivi']);

        return sprintf(
            'Le signalement à été cloturé pour %s avec le motif suivant <br><strong>%s</strong><br><strong>Desc. : </strong>%s',
            $params['subject'],
            $params['motif_cloture'],
            $motifSuivi
        );
    }

    private function buildDescriptionAnswerAffectation($params): string
    {
        $description = '';
        if (isset($params['accept'])) {
            $description = 'Le signalement a été accepté';
        } elseif (isset($params['suivi'])) {
            $motifRejected = Sanitizer::sanitize($params['suivi']);
            $description = 'Le signalement à été refusé avec le motif suivant:<br> '.$motifRejected;
        }

        return $description;
    }
}
