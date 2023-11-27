<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class DesordreTraitementDispatcher
{
    private iterable $desordreTraitements;

    public function __construct(
        #[TaggedIterator('desordre_traitement', indexAttribute: 'key')]
        iterable $desordreTraitements
    ) {
        $this->desordreTraitements = $desordreTraitements;
    }

    public function dispatch(DesordreCritere $critere, array $payload): ArrayCollection
    {
        $slug = $critere->getSlugCritere();
        $desordreTraitementsHandlers = $this->desordreTraitements instanceof \Traversable ? iterator_to_array($this->desordreTraitements) : $this->desordreTraitements;

        $desordreCritereProcessor = $desordreTraitementsHandlers[$slug];
        if ($desordreCritereProcessor) {
            $desordrePrecisions = $desordreCritereProcessor->process($critere, $payload);

            return $desordrePrecisions;
        }
        // TODO : renvoyer un tableau vide ou une erreur si on ne trouve pas de desordreCritereProcessor lié au slug
        return new ArrayCollection();
    }
}
