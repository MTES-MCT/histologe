<?php

namespace App\Factory;

use App\Entity\Suivi;
use Symfony\Component\Security\Core\Security;

class SuiviFactory
{
    public function __construct(private Security $security)
    {
    }

    public function createInstanceFrom(array $params)
    {
        $motifSuivi = preg_replace('/<p[^>]*>/', '', $params['motif_suivi']); // Remove the start <p> or <p attr="">
        $motifSuivi = str_replace('</p>', '<br>', $motifSuivi); // Replace the end
        $suivi = (new Suivi())
            ->setDescription(
                'Le signalement à été cloturé pour '
                .$params['subject'].' avec le motif suivant: <br> <strong>'
                .$params['motif_cloture'].'</strong><br><strong>Desc.: </strong>'
                .$motifSuivi
            )
            ->setCreatedBy($this->security->getUser())
            ->setIsPublic(true);

        return $suivi;
    }
}
