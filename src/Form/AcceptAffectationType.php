<?php

namespace App\Form;

use App\Dto\AcceptAffectation;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcceptAffectationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var AcceptAffectation $acceptAffectation */
        $acceptAffectation = $builder->getData();
        $choicesAgents = $acceptAffectation->getAffectation()->getPartner()->getUsers();

        $builder->add('agents', EntityType::class, [
            'class' => User::class,
            'choices' => $choicesAgents,
            'choice_label' => fn (User $user) => $this->getAgentLabel($user),
            'multiple' => true,
            'expanded' => true,
            'label_html' => true,
            'label' => 'Sélectionnez le(s) agent(s) en charge du dossier',
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

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AcceptAffectation::class,
        ]);
    }
}
