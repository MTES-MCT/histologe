<?php

namespace App\Utils;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DictionaryProvider
{
    private ?array $dictionary = null;

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDir,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $path = $this->projectDir.'/public/build/json/Signalement/dictionary.json';

        if (!is_file($path)) {
            return [];
        }

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    public function translate(string $slug, string $context = 'default'): string
    {
        if (null === $this->dictionary) {
            $this->dictionary = $this->all();
        }
        $dict = $this->dictionary;

        if (!isset($dict[$slug])) {
            return $slug;
        }

        if (isset($dict[$slug]['default']) && is_string($dict[$slug]['default']) && 'default' === $context) {
            return $dict[$slug]['default'];
        }

        if (isset($dict[$slug][$context]['default']) && is_string($dict[$slug][$context]['default'])) {
            return $dict[$slug][$context]['default'];
        }

        if (isset($dict[$slug]['default']) && is_string($dict[$slug]['default'])) {
            return $dict[$slug]['default'];
        }

        return $slug;
    }
}
