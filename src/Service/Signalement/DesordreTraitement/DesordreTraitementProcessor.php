<?php

namespace App\Service\Signalement\DesordreTraitement;

use App\Entity\DesordreCritere;
use App\Entity\DesordrePrecision;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class DesordreTraitementProcessor
{
    /**
     * @var iterable<string, DesordreTraitementInterface>
     */
    private iterable $desordreTraitements;

    /**
     * @param iterable<string, DesordreTraitementInterface> $desordreTraitements
     */
    public function __construct(
        #[AutowireIterator('desordre_traitement', indexAttribute: 'key')]
        iterable $desordreTraitements,
    ) {
        $this->desordreTraitements = $desordreTraitements;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return ?array<int, DesordrePrecision>
     */
    public function findDesordresPrecisionsBy(DesordreCritere $critere, array $payload): ?array
    {
        $slug = $critere->getSlugCritere();
        $desordreTraitementsHandlers = $this->desordreTraitements instanceof \Traversable ?
            iterator_to_array($this->desordreTraitements) :
            $this->desordreTraitements;

        if (\array_key_exists($slug, $desordreTraitementsHandlers)) {
            $desordreCritereHandler = $desordreTraitementsHandlers[$slug];
            $desordrePrecisions = $desordreCritereHandler->findDesordresPrecisionsBy($payload, $slug);

            return $desordrePrecisions;
        }

        return null;
    }
}
