<?php

namespace App\Form;

use App\Dto\TransferSubscription;
use App\Entity\Enum\UserStatus;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TransferSubscriptionType extends AbstractType
{
    public function __construct(
        private readonly Security $security,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var TransferSubscription $transferSubscription */
        $transferSubscription = $builder->getData();
        /** @var User $user */
        $user = $this->security->getUser();
        $choicesAgents = $transferSubscription
            ->getAffectation()
            ->getPartner()
            ->getUsers()
            ->filter(fn (User $u) => $u->getId() !== $user->getId());

        $builder->add('agents', EntityType::class, [
            'class' => User::class,
            'choices' => $choicesAgents,
            'choice_label' => fn (User $user) => $this->getAgentLabel($user),
            'multiple' => true,
            'expanded' => true,
            'label_html' => true,
            'label' => 'Sélectionnez le(s) agent(s) à qui transmettre le dossier',
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
            'data_class' => TransferSubscription::class,
        ]);
    }
}
