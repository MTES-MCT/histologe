<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPartnerType extends AbstractType
{
    private array $roles;

    public function __construct(
        private readonly Security $security,
    ) {
        $this->roles = [];
        if ($security->isGranted('ROLE_ADMIN')) {
            $this->roles['Super Admin'] = 'ROLE_ADMIN';
        }
        if ($security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $this->roles['Resp. Territoire'] = 'ROLE_ADMIN_TERRITORY';
        }
        $this->roles['Admin. partenaire'] = 'ROLE_ADMIN_PARTNER';
        $this->roles['Agent'] = 'ROLE_USER_PARTNER';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $builder->getData();
        $builder
            ->add('emailDisplay', null, [
                'mapped' => false,
                'disabled' => true,
                'label' => 'Courriel',
                'help' => 'Un e-mail d\'activation du compte sera envoyé à cette adresse e-mail.',
                'data' => $user->getEmail(),
            ])
            ->add('email', HiddenType::class)
            ->add('nom', null, [
                'required' => false,
            ])
            ->add('prenom', null, [
                'required' => false,
            ])
            ->add('role', ChoiceType::class, [
                // TODO si utilisé en édition : role limité pour les user multi territoire
                'choices' => $this->roles,
                'label' => 'Rôle',
                'mapped' => false,
            ])
            ->add('isMailingActive', ChoiceType::class, [
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'label' => 'Recevoir les e-mails ?',
                'help' => 'Si vous cochez oui, des e-mails concernant les signalements seront envoyés à cette adresse.',
            ]);
        if (1 === $user->getUserPartners()->count() && $this->security->isGranted('ASSIGN_PERMISSION_AFFECTATION', $user->getPartners()->first())) {
            $builder->add('hasPermissionAffectation', CheckboxType::class, [
                'label' => 'Cet utilisateur peut affecter d\'autres partenaires à ses signalements',
                'required' => false,
                'row_attr' => [
                    'class' => 'fr-toggle',
                ],
                'attr' => ['class' => 'fr-toggle__input'],
                'label_attr' => [
                    'class' => 'fr-toggle__label',
                    'data-fr-checked-label' => 'Activé',
                    'data-fr-unchecked-label' => 'Désactivé',
                ],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups' => ['user_partner_mail', 'user_partner'],
        ]);
    }
}
