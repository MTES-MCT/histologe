<?php

namespace App\Tests\Functional\Controller;

use App\Repository\SignalementRepository;
use App\Tests\SessionHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class HomepageControllerTest extends WebTestCase
{
    use SessionHelper;

    private KernelBrowser $client;
    private RouterInterface $router;
    private SignalementRepository $signalementRepository;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->router = static::getContainer()->get(RouterInterface::class);
        $this->signalementRepository = static::getContainer()->get(SignalementRepository::class);
    }

    public function testDemandeLienSignalementPostWithMatchingSignalement(): void
    {
        $signalement = $this->signalementRepository->findOneBy(['reference' => '2022-1']);
        $this->assertNotNull($signalement);

        $address = $signalement->getAddress();
        $this->assertNotNull($address);

        $email = $signalement->getMailDeclarant();
        $this->assertNotNull($email);

        $adresse = $address->getHousenumberAndStreet();
        $codePostal = $address->getPostCode();
        $ville = $address->getCity();

        $this->client->request('POST', $this->router->generate('front_demande_lien_signalement'), [
            'demande_lien_signalement' => [
                'email' => $email,
                'adresseHelper' => sprintf('%s %s %s', $adresse, $codePostal, $ville),
                'adresse' => $adresse,
                'codePostal' => $codePostal,
                'ville' => $ville,
                '_token' => $this->generateCsrfToken($this->client, 'demande_lien_signalement'),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        /** @var array{html?: string} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('html', $data);
        $this->assertStringContainsString('Si un signalement correspond aux informations saisies', $data['html']);

        $this->assertEmailCount(1);
        $mail = $this->getMailerMessages()[0];
        $this->assertEmailAddressContains($mail, 'to', $email);
        $this->assertEmailSubjectContains($mail, 'Lien vers votre page de suivi');
    }

    public function testDemandeLienSignalementPostWithoutMatchingSignalement(): void
    {
        $this->client->request('POST', $this->router->generate('front_demande_lien_signalement'), [
            'demande_lien_signalement' => [
                'email' => 'inconnu@example.com',
                'adresseHelper' => '10 rue Inconnue 75001 Paris',
                'adresse' => '10 rue Inconnue',
                'codePostal' => '75001',
                'ville' => 'Paris',
                '_token' => $this->generateCsrfToken($this->client, 'demande_lien_signalement'),
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        /** @var array{html?: string} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('html', $data);
        $this->assertStringContainsString('Si un signalement correspond aux informations saisies', $data['html']);

        $this->assertEmailCount(0);
    }

    /**
     * @param array<string, string> $payload
     */
    #[DataProvider('provideInvalidDemandeLienSignalement')]
    public function testDemandeLienSignalementPostWithInvalidForm(array $payload, string $expectedMessage): void
    {
        $payload['_token'] = $this->generateCsrfToken($this->client, 'demande_lien_signalement');

        $this->client->request('POST', $this->router->generate('front_demande_lien_signalement'), [
            'demande_lien_signalement' => $payload,
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        /** @var array{html?: string} $data */
        $data = json_decode((string) $this->client->getResponse()->getContent(), true, 512, \JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('html', $data);
        $this->assertStringContainsString('id="form-demande-lien-signalement"', $data['html']);
        $this->assertStringContainsString($expectedMessage, $data['html']);

        $this->assertEmailCount(0);
    }

    public static function provideInvalidDemandeLienSignalement(): \Generator
    {
        yield 'adresse non sélectionnée' => [
            'payload' => [
                'email' => 'usager@example.com',
                'adresseHelper' => 'adresse non selectionnee',
                'adresse' => '',
                'codePostal' => '',
                'ville' => '',
            ],
            'expectedMessage' => 'Vous devez sélectionner une adresse dans la liste des propositions',
        ];

        yield 'formulaire vide' => [
            'payload' => [
                'email' => '',
                'adresseHelper' => '',
                'adresse' => '',
                'codePostal' => '',
                'ville' => '',
            ],
            'expectedMessage' => 'Vous devez sélectionner une adresse dans la liste des propositions',
        ];

        yield 'email invalide' => [
            'payload' => [
                'email' => 'email-invalide',
                'adresseHelper' => '10 rue Inconnue 75001 Paris',
                'adresse' => '10 rue Inconnue',
                'codePostal' => '75001',
                'ville' => 'Paris',
            ],
            'expectedMessage' => 'Cette valeur n&#039;est pas une adresse e-mail valide.',
        ];
    }
}
