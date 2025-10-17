<?php

namespace App\Form;

use App\Dto\AgentSelection;
use App\Entity\Affectation;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AgentSelectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AgentSelection $agentSelection */
        $agentSelection = $builder->getData();
        /** @var Affectation $affectation */
        $affectation = $agentSelection->getAffectation();
        $choicesAgents = $affectation->getPartner()->getUsers();

        if ($options['exclude_user'] instanceof User) {
            $choicesAgents = $choicesAgents->filter(fn (User $u) => $u->getId() !== $options['exclude_user']->getId());
        }

        $builder->add('agents', EntityType::class, [
            'class' => User::class,
            'choices' => $choicesAgents,
            'choice_label' => fn (User $user) => $this->getAgentLabel($user),
            'multiple' => true,
            'expanded' => true,
            'label_html' => true,
            'label' => '<strong>' . htmlspecialchars($options['label']) . '</strong>',
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

    public function getBlockPrefix(): string
    {
        return 'agents_selection';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AgentSelection::class,
            'exclude_user' => null,
            'label' => 'Sélectionnez le(s) agent(s) en charge du dossier',
        ]);
    }
}
