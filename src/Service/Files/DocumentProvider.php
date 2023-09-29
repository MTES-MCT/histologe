<?php

namespace App\Service\Files;

use App\Entity\Signalement;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class DocumentProvider
{
    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function getModeleCourrierPourProprietaire(Signalement $signalement): ?string
    {
        if (!$signalement->getIsProprioAverti()
            && file_exists($modeleCourrier = $this->parameterBag->get('mail_attachment_dir').'ModeleCourrier.pdf')) {
            return $modeleCourrier;
        }

        return null;
    }
}
