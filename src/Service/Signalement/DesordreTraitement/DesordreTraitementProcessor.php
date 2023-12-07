<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class DesordreTraitementProcessor
{
    private iterable $desordreTraitements;

    public function __construct(
        #[TaggedIterator('desordre_traitement', indexAttribute: 'key')]
        iterable $desordreTraitements
    ) {
        $this->desordreTraitements = $desordreTraitements;
    }

    public function process(DesordreCritere $critere, array $payload): ArrayCollection|null
    {
        $slug = $critere->getSlugCritere();
        $desordreTraitementsHandlers = $this->desordreTraitements instanceof \Traversable ?
            iterator_to_array($this->desordreTraitements) :
            $this->desordreTraitements;

        if (\array_key_exists($slug, $desordreTraitementsHandlers)) {
            $desordreCritereProcessor = $desordreTraitementsHandlers[$slug];
            if ($desordreCritereProcessor) {
                $desordrePrecisions = $desordreCritereProcessor->process($payload, $slug);

                return $desordrePrecisions;
            }
        }

        return null;
    }
}
