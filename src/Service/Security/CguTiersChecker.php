<?php

namespace App\Service\Security;

use App\Entity\Signalement;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CguTiersChecker
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function redirectIfTiersNeedsToAcceptCgu(Signalement $signalement, ?string $userEmail): ?Response
    {
        if ($userEmail !== $signalement->getMailDeclarant()) {
            return null;
        }

        // If null, not invited yet ; if true, already accepted
        if (false !== $signalement->getIsCguTiersAccepted()) {
            return null;
        }

        return new RedirectResponse($this->urlGenerator->generate('suivi_signalement_tiers_cgu_accept', ['code' => $signalement->getCodeSuivi()]));
    }
}
