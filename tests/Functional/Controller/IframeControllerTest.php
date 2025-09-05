<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class IframeControllerTest extends WebTestCase
{
    public function testSubmitDemandeLienSignalement(): void
    {
        $client = static::createClient();
        /** @var UrlGeneratorInterface $generatorUrl */
        $generatorUrl = static::getContainer()->get(UrlGeneratorInterface::class);
        $client->request('GET', $generatorUrl->generate('iframe_demande_lien_signalement'));

        $client->submitForm('demande_lien_signalement_save', [
            'demande_lien_signalement[email]' => 'admin-partenaire-13-01@signal-logement.fr',
            'demande_lien_signalement[adresseHelper]' => '3 rue Mars 13015 Marseille',
            'demande_lien_signalement[adresse]' => '3 rue Mars',
            'demande_lien_signalement[codePostal]' => '13015',
            'demande_lien_signalement[ville]' => 'Marseille',
        ]);

        $this->assertEmailCount(1);
        $responseContent = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertStringContainsString('Si un signalement correspond aux informations saisies', $responseContent['html']);
    }
}
