<?php

namespace App\Form;

use App\Dto\AgentSelection;
use App\Entity\Enum\UserStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Entity\UserSignalementSubscription;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class AgentSelectionType extends AbstractType
{
    private User $currentUser;

    public function __construct(
        private readonly UserRepository $userRepository,
        Security $security,
    ) {
        /** @var User $user */
        $user = $security->getUser();
        $this->currentUser = $user;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AgentSelection $agentSelection */
        $agentSelection = $builder->getData();
        /** @var Signalement $signalement */
        $signalement = $agentSelection->getSignalement();

        $choicesAgents = $this->getChoicesAgents($signalement, $options);
        $existingSubscriptions = $signalement->getUserSignalementSubscriptions();
        $existingSubscriptionsInChoices = array_filter($existingSubscriptions->toArray(), function (UserSignalementSubscription $sub) use ($choicesAgents) {
            foreach ($choicesAgents as $agent) {
                if ($sub->getUser()->getId() === $agent->getId()) {
                    return true;
                }
            }

            return false;
        });
        $preselectedAgents = [];
        foreach ($agentSelection->getAgents() as $agent) {
            $preselectedAgents[$agent->getId()] = $agent;
        }
        foreach ($existingSubscriptionsInChoices as $subscription) {
            $preselectedAgents[$subscription->getUser()->getId()] = $subscription->getUser();
        }
        $constraints = [];
        if (!$existingSubscriptionsInChoices) {
            $label = 'Veuillez sélectionner au moins un agent.';
            if ($options['only_rt']) {
                $label = 'Veuillez sélectionner au moins un responsable de territoire.';
            }
            $constraints[] = new Assert\Count(['min' => 1, 'minMessage' => $label]);
        }

        $builder->add('agents', EntityType::class, [
            'class' => User::class,
            'choices' => $choicesAgents,
            'choice_label' => fn (User $user) => $this->getAgentLabel($user),
            'choice_attr' => function (User $user) use ($existingSubscriptions) {
                foreach ($existingSubscriptions as $sub) {
                    if ($sub->getUser()->getId() === $user->getId()) {
                        return ['disabled' => 'disabled'];
                    }
                }

                return [];
            },
            'multiple' => true,
            'expanded' => true,
            'label_html' => true,
            'label' => '<strong>'.htmlspecialchars($options['label']).'</strong>',
            'data' => $preselectedAgents,
            'constraints' => $constraints,
        ]);
    }

    private function getAgentLabel(User $agent): string
    {
        $html = $agent->getNomComplet(true).' ('.$agent->getEmail().')';
        if (UserStatus::INACTIVE === $agent->getStatut()) {
            $html .= '<small class="fr-hint-text fr-text--text-default-warning">';
            $html .= '<span class="fr-icon--sm fr-icon-warning-line" aria-hidden="true"></span> '.$agent->getRoleLabel().' -  Compte inactif';
            $html .= '</small>';
        } else {
            $html .= '<small class="fr-hint-text fr-text-default--grey">'.$agent->getRoleLabel().'</small>';
        }

        return $html;
    }

    private function getChoicesAgents(Signalement $signalement, array $options): array
    {
        $choicesAgents = [];
        $partner = $this->currentUser->getPartnerInTerritoryOrFirstOne($signalement->getTerritory());

        if ($options['only_rt']) {
            if ($this->currentUser->isSuperAdmin()) {
                $choicesAgents = $this->userRepository->findActiveTerritoryAdmins($signalement->getTerritory()->getId());
            } elseif ($this->currentUser->isTerritoryAdmin()) {
                $choicesAgents = $this->userRepository->findActiveTerritoryAdminsInPartner($partner);
            }
        } else {
            $choicesAgents = $partner->getUsers()->toArray();
        }

        if ($options['exclude_user'] instanceof User) {
            $choicesAgents = array_filter($choicesAgents, fn (User $u) => $u->getId() !== $options['exclude_user']->getId());
        }

        return $choicesAgents;
    }

    public function getBlockPrefix(): string
    {
        return 'agents_selection';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgentSelection::class,
            'exclude_user' => null,
            'only_rt' => false,
            'label' => 'Sélectionnez le(s) agent(s) abonné(s) au dossier',
            'validation_groups' => function (Options $options) {
                return $options['only_rt'] ? ['only_rt'] : null;
            },
        ]);
    }
}
