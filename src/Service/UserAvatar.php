<?php

namespace App\Service;

use App\Entity\User;
use League\Flysystem\FilesystemOperator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Twig\Extension\RuntimeExtensionInterface;

class UserAvatar implements RuntimeExtensionInterface
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly FilesystemOperator $fileStorage,
    ) {
    }

    public function userAvatarOrPlaceholder(User $user, int $size = 74, bool $ariaHidden = true): string
    {
        $zipCode = ($user->getFirstTerritory()) ? substr($user->getFirstTerritory()->getZip(), 0, 2) : 'SA';

        $filename = $user->getAvatarFilename();

        if (null === $filename || !$this->fileStorage->fileExists($filename)) {
            return $this->renderPlaceholder($zipCode, $size, $ariaHidden);
        }

        $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$filename;
        $data = @file_get_contents($bucketFilepath);

        if (false === $data) {
            return $this->renderPlaceholder($zipCode, $size, $ariaHidden);
        }

        $type = pathinfo($bucketFilepath, \PATHINFO_EXTENSION);
        $data64 = base64_encode($data);
        $src = "data:image/$type;base64,$data64";

        $ariaHiddenAttribute = $ariaHidden
            ? 'alt="" aria-hidden="true"'
            : 'alt="Avatar de l\'utilisateur"';

        return sprintf(
            '<img src="%s" %s class="avatar-histologe avatar-%s">',
            $src,
            $ariaHiddenAttribute,
            $size
        );
    }

    private function renderPlaceholder(string $zipCode, int $size, bool $ariaHidden): string
    {
        $ariaHiddenAttribute = $ariaHidden ? 'aria-hidden="true"' : '';

        return sprintf(
            '<span %s class="avatar-histologe avatar-placeholder avatar-%s">%s</span>',
            $ariaHiddenAttribute,
            $size,
            $zipCode
        );
    }
}
