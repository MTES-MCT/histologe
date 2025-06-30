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

        if ($user->getAvatarFilename() && $this->fileStorage->fileExists($user->getAvatarFilename())) {
            $bucketFilepath = $this->parameterBag->get('url_bucket').'/'.$user->getAvatarFilename();

            $type = pathinfo($bucketFilepath, \PATHINFO_EXTENSION);

            $data = file_get_contents($bucketFilepath);
            $data64 = base64_encode($data);

            $src = "data:image/$type;base64,$data64";

            $arriaHiddenAttribute = $ariaHidden ? 'alt="" aria-hidden="true"' : 'alt="Avatar de l\'utilisateur"';

            return sprintf(
                '<img src="%s" '.$arriaHiddenAttribute.' class="avatar-histologe avatar-%s">',
                $src,
                $size
            );
        }

        $arriaHiddenAttribute = $ariaHidden ? 'aria-hidden="true"' : '';

        return sprintf(
            '<span '.$arriaHiddenAttribute.' class="avatar-histologe avatar-placeholder avatar-%s">%s</span>',
            $size,
            $zipCode
        );
    }
}
