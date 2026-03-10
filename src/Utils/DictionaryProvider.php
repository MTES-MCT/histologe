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

        $result = $slug;

        if (isset($dict[$slug])) {
            $entry = $dict[$slug];

            if (isset($entry[$context]) && is_string($entry[$context])) {
                $result = $entry[$context];
            } elseif (isset($entry[$context]['default']) && is_string($entry[$context]['default'])) {
                $result = $entry[$context]['default'];
            } elseif (isset($entry['default']) && is_string($entry['default'])) {
                $result = $entry['default'];
            }
        }

        return $result;
    }
}
