<?php

namespace App\Form;

use App\Dto\AcceptSignalement;
use App\Entity\Enum\UserStatus;
use App\Entity\Signalement;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AcceptSignalementType extends AbstractType
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
        /** @var Signalement $signalement */
        $signalement = $builder->getData()->getSignalement();
        if ($this->currentUser->isTerritoryAdmin()) {
            $choicesAgents = $this->userRepository->findActiveTerritoryAdminsInPartner($this->currentUser->getPartnerInTerritory($signalement->getTerritory()));
        } else {
            $choicesAgents = $this->userRepository->findActiveTerritoryAdmins($signalement->getTerritory()->getId());
        }

        $builder->add('agents', EntityType::class, [
            'class' => User::class,
            'choices' => $choicesAgents,
            'choice_label' => fn (User $user) => $this->getAgentLabel($user),
            'multiple' => true,
            'expanded' => true,
            'label_html' => true,
            'label' => '<strong>'.htmlspecialchars($options['label']).'</strong>',
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
        return 'accept_signalement';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AcceptSignalement::class,
            'label' => 'Sélectionnez le(s) agent(s) en charge du dossier',
        ]);
    }
}
