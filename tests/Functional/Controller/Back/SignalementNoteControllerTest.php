<?php

namespace App\Tests\Functional\Controller\Back;

use App\Entity\Signalement;
use App\Repository\PersonalNoteRepository;
use App\Repository\PersonalTagRepository;
use App\Repository\SignalementRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class SignalementNoteControllerTest extends WebTestCase
{
    use SessionHelper;

    private const string SIGNALEMENT_UUID = '00000000-0000-0000-2023-000000000006';

    private KernelBrowser $client;
    private RouterInterface $router;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();
        $this->router = static::getContainer()->get(RouterInterface::class);
    }

    #[DataProvider('provideUsers')]
    public function testAddAndRemovePersonalTag(string $email): void
    {
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);
        $userId = $user->getId();
        $this->client->loginUser($user);

        $this->client->request('POST', $this->router->generate('back_signalement_create_personal_tag', [
            'uuid' => self::SIGNALEMENT_UUID,
        ]), [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_create_personal_tag'),
            'label' => 'Etiquette de test',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        $signalement = $this->getSignalement();
        $tag = static::getContainer()->get(PersonalTagRepository::class)->findOneBy([
            'label' => 'Etiquette de test',
            'user' => $userId,
        ]);
        self::assertNotNull($tag);
        $tagId = $tag->getId();
        self::assertTrue($signalement->getPersonalTags()->contains($tag));

        $this->client->request('POST', $this->router->generate('back_signalement_save_personal_tags', [
            'uuid' => self::SIGNALEMENT_UUID,
        ]), [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_save_personal_tags'),
            'tag-ids' => '',
        ]);

        self::assertResponseIsSuccessful();
        $signalement = $this->getSignalement();
        $tag = static::getContainer()->get(PersonalTagRepository::class)->find($tagId);
        self::assertNotNull($tag);
        self::assertFalse($signalement->getPersonalTags()->contains($tag));
    }

    #[DataProvider('provideUsers')]
    public function testAddAndDeletePersonalNote(string $email): void
    {
        $user = static::getContainer()->get(UserRepository::class)->findOneBy(['email' => $email]);
        self::assertNotNull($user);
        $userId = $user->getId();
        $this->client->loginUser($user);

        $this->client->request('POST', $this->router->generate('back_signalement_save_personal_note', [
            'uuid' => self::SIGNALEMENT_UUID,
        ]), [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_save_personal_note'),
            'content' => '<p>Ma note personnelle de test.</p>',
        ]);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('Content-Type', 'application/json');
        $signalement = $this->getSignalement();
        $criteria = ['user' => $userId, 'signalement' => $signalement->getId()];
        $note = static::getContainer()->get(PersonalNoteRepository::class)->findOneBy($criteria);
        self::assertNotNull($note);
        self::assertSame('<p>Ma note personnelle de test.</p>', $note->getContent());

        $this->client->request('POST', $this->router->generate('back_signalement_delete_personal_note', [
            'uuid' => self::SIGNALEMENT_UUID,
        ]), [
            '_token' => $this->generateCsrfToken($this->client, 'signalement_delete_personal_note'),
        ]);

        self::assertResponseIsSuccessful();
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        self::assertNull(static::getContainer()->get(PersonalNoteRepository::class)->findOneBy($criteria));
    }

    public static function provideUsers(): \Generator
    {
        yield 'Responsable de territoire' => ['admin-territoire-13-01@signal-logement.fr'];
        yield 'Agent' => ['user-13-01@signal-logement.fr'];
    }

    private function getSignalement(): Signalement
    {
        static::getContainer()->get(EntityManagerInterface::class)->clear();
        $signalement = static::getContainer()->get(SignalementRepository::class)->findOneBy([
            'uuid' => self::SIGNALEMENT_UUID,
        ]);
        self::assertNotNull($signalement);

        return $signalement;
    }
}
