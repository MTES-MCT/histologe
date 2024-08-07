<?php

namespace App\Tests\Functional\Controller\Back;

use App\Repository\TagRepository;
use App\Repository\UserRepository;
use App\Tests\SessionHelper;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Routing\RouterInterface;

class BackTagControllerTest extends WebTestCase
{
    use SessionHelper;

    private ?KernelBrowser $client = null;
    private UserRepository $userRepository;
    private RouterInterface $router;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->userRepository = static::getContainer()->get(UserRepository::class);
        $this->router = self::getContainer()->get(RouterInterface::class);

        $user = $this->userRepository->findOneBy(['email' => 'admin-01@histologe.fr']);
        $this->client->loginUser($user);
    }

    public function testCreateTagSuccess(): void
    {
        $route = $this->router->generate('back_tag_create', ['uuid' => '00000000-0000-0000-2023-000000000006']);
        $this->client->request('POST', $route, ['new-tag-label' => 'test']);

        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('success', $this->client->getResponse()->getContent());
    }

    public function testCreateTagError(): void
    {
        $route = $this->router->generate('back_tag_create', ['uuid' => '00000000-0000-0000-2023-000000000006']);
        $this->client->request('POST', $route, ['new-tag-label' => 't']);

        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertStringContainsString('Le tag doit contenir au moins 2 caract\u00e8res', $this->client->getResponse()->getContent());
    }

    public function testSearchNewTag(): void
    {
        $route = $this->router->generate('back_tags_index');
        $this->client->request('POST', $route, ['territory' => 13, 'search' => 'Urgent']);
        $this->client->followRedirect();
        $this->assertSelectorTextContains('#desc-table', '1 étiquette');
    }

    public function testDeleteNewTagSuccess(): void
    {
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepository->findOneBy(['label' => 'Commission du 12/08', 'isArchive' => 0]);
        $route = $this->router->generate('back_tags_delete');
        $this->client->request(
            'POST',
            $route,
            [
                'tag_id' => $tag->getId(),
                '_token' => $this->generateCsrfToken($this->client, 'tag_delete'),
            ]
        );
        $tag = $tagRepository->findOneBy(['label' => 'Commission du 12/08', 'isArchive' => 0]);
        $this->assertNull($tag);

        $this->client->followRedirect();
        $this->assertSelectorTextContains('#desc-table', '5 étiquettes');
    }

    public function testDeleteNewTagFailed(): void
    {
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $tag = $tagRepository->findOneBy(['label' => 'Commission du 12/08', 'isArchive' => 0]);
        $route = $this->router->generate('back_tags_delete');
        $this->client->request(
            'POST',
            $route,
            [
                'tag_id' => $tag->getId(),
            ]
        );
        $tag = $tagRepository->findOneBy(['label' => 'Commission du 12/08', 'isArchive' => 0]);
        $this->assertNotNull($tag);

        $this->client->followRedirect();
        $this->assertSelectorTextContains('#desc-table', '6 étiquettes');
    }

    /**
     * @dataProvider provideTagDataForCreateForm
     */
    public function testCreateNewTag(
        string $tagLabel,
        string $tagTerritory,
        bool $withCsrfToken,
        string $selector,
        string $message
    ): void {
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $route = $this->router->generate('back_tags_add');
        $this->client->request(
            'POST',
            $route,
            [
                'tag_label' => $tagLabel,
                'tag_territory' => $tagTerritory,
                '_token' => $withCsrfToken ? $this->generateCsrfToken($this->client, 'tag_add') : 'invalid-token',
            ]
        );

        if ('.fr-alert--success' === $selector) {
            $tag = $tagRepository->findOneBy(['label' => 'Moisissure', 'isArchive' => 0, 'territory' => '13']);
            $this->assertNotNull($tag);
        }

        $this->client->followRedirect();
        $this->assertSelectorTextContains($selector, $message);
    }

    /**
     * @dataProvider provideTagDataForEditForm
     */
    public function testEditNewTag(
        string $tagId,
        string $tagLabel,
        bool $withCsrfToken,
        string $selector,
        string $message
    ): void {
        /** @var TagRepository $tagRepository */
        $tagRepository = self::getContainer()->get(TagRepository::class);
        $route = $this->router->generate('back_tags_edit');
        $this->client->request(
            'POST',
            $route,
            [
                'tag_id' => $tagId,
                'tag_label' => $tagLabel,
                '_token' => $withCsrfToken ? $this->generateCsrfToken($this->client, 'tag_edit') : 'invalid-token',
            ]
        );

        if ('.fr-alert--success' === $selector) {
            $tag = $tagRepository->find($tagId);
            $this->assertEquals('Urgent MAJ', $tag->getLabel());
        }

        $this->client->followRedirect();
        $this->assertSelectorTextContains($selector, $message);
    }

    public function provideTagDataForCreateForm(): \Generator
    {
        yield 'Success with all data' => [
            'Moisissure',
            '13',
            true,
            '.fr-alert--success',
            'L\'étiquette a bien été ajoutée.',
        ];

        yield 'Failed with existing tag label' => [
            'Péril',
            '13',
            true,
            '.fr-alert--error',
            'Une étiquette avec le même nom existe déjà sur ce territoire...',
        ];

        yield 'Failed with empty tag label' => [
            '',
            '13',
            true,
            '.fr-alert--error',
            'Merci de saisir un nom pour l\'étiquette.',
        ];

        yield 'Failed with csrf token missing' => [
            'Moisissure',
            '13',
            false,
            '.fr-alert--error',
            'Une erreur est survenue lors de l\'ajout...',
        ];
    }

    public function provideTagDataForEditForm(): \Generator
    {
        yield 'Success with all data' => [
            '1',
            'Urgent MAJ',
            true,
            '.fr-alert--success',
            'L\'étiquette a bien été éditée.',
        ];

        yield 'Failed with empty tag label' => [
            '1',
            '',
            true,
            '.fr-alert--error',
            'Merci de saisir un nom pour l\'étiquette.',
        ];

        yield 'Failed with existing tag label' => [
            '1',
            'Péril',
            true,
            '.fr-alert--error',
            'Une étiquette avec le même nom existe déjà sur ce territoire...',
        ];

        yield 'Failed with csrf token missing' => [
            '1',
            'Urgent Nouveau',
            false,
            '.fr-alert--error',
            'Une erreur est survenue lors de l\'édition...',
        ];
    }
}
