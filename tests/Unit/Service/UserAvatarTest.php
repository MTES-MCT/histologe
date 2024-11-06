<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Entity\UserPartner;
use App\Service\UserAvatar;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class UserAvatarTest extends WebTestCase
{
    public function testUserAvatarOrPlaceHolder(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $userAvatar = $container->get(UserAvatar::class);

        $user = (new User())
        ->setRoles([User::ROLES['Super Admin']]);
        $outputSpan = $userAvatar->userAvatarOrPlaceHolder($user);
        $this->assertEquals('<span class="avatar-histologe avatar-placeholder avatar-74">SA</span>', $outputSpan);

        $outputSpan = $userAvatar->userAvatarOrPlaceHolder($user, 80);
        $this->assertEquals('<span class="avatar-histologe avatar-placeholder avatar-80">SA</span>', $outputSpan);

        $user->setRoles([User::ROLES['Admin. partenaire']]);
        $territory = (new Territory())->setZip('44');
        $partner = (new Partner())->setTerritory($territory);
        $userPartner = (new UserPartner())->setPartner($partner)->setUser($user);
        $user->addUserPartner($userPartner);
        $outputSpan = $userAvatar->userAvatarOrPlaceHolder($user);
        $this->assertEquals('<span class="avatar-histologe avatar-placeholder avatar-74">44</span>', $outputSpan);

        /** @var ParameterBagInterface $parameterBag */
        $parameterBag = $this->createMock(ParameterBagInterface::class);
        /** @var FilesystemOperator|MockObject $fileStorage */
        $fileStorage = $this->createMock(FilesystemOperator::class);
        $fileStorage->expects($this->exactly(2))
            ->method('fileExists')
            ->willReturn(true);

        $userAvatar = new UserAvatar(
            $parameterBag,
            $fileStorage,
        );

        $user->setAvatarFilename(__DIR__.'/../../files/sample.jpg');
        $outputSpan = $userAvatar->userAvatarOrPlaceHolder($user);

        $this->assertStringContainsString('<img src="data:image/jpg;base64,', $outputSpan);
        $this->assertStringContainsString('alt="Avatar de l\'utilisateur" class="avatar-histologe avatar-74">', $outputSpan);

        $outputSpan = $userAvatar->userAvatarOrPlaceHolder($user, 100);

        $this->assertStringContainsString('<img src="data:image/jpg;base64,', $outputSpan);
        $this->assertStringContainsString('alt="Avatar de l\'utilisateur" class="avatar-histologe avatar-100">', $outputSpan);
    }
}
