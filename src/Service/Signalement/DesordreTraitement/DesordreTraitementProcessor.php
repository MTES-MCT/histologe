<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
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

    public function findDesordresPrecisionsBy(DesordreCritere $critere, array $payload): array|null
    {
        $slug = $critere->getSlugCritere();
        $desordreTraitementsHandlers = $this->desordreTraitements instanceof \Traversable ?
            iterator_to_array($this->desordreTraitements) :
            $this->desordreTraitements;

        if (\array_key_exists($slug, $desordreTraitementsHandlers)) {
            $desordreCritereHandler = $desordreTraitementsHandlers[$slug];
            if ($desordreCritereHandler) {
                $desordrePrecisions = $desordreCritereHandler->findDesordresPrecisionsBy($payload, $slug);

                return $desordrePrecisions;
            }
        }

        return null;
    }
}
