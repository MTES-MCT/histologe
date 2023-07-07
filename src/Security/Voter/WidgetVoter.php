<?php

namespace App\Security\Voter;

use App\Entity\User;
use App\Service\DashboardWidget\Widget;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class WidgetVoter extends Voter
{
    public const VIEW_WIDGET = 'VIEW_WIDGET';

    public function __construct(private ParameterBagInterface $parameterBag)
    {
    }

    public function supports(string $attribute, mixed $subject): bool
    {
        return \in_array($attribute, [self::VIEW_WIDGET])
            && ($subject instanceof Widget || !$subject);
    }

    public function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        /** @var User $user */
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return $this->canViewWidget($subject, $user);
    }

    public function canViewWidget(Widget $widget, UserInterface $user): bool
    {
        if (!$this->parameterBag->has($widget->getType())) { // ignore voter if widget does not exists
            return true;
        }

        $role = $user->getRoles();
        $widgetParams = $this->parameterBag->get($widget->getType());

        return \in_array(array_shift($role), $widgetParams['roles']);
    }
}
