<?php

namespace App\Form;

use App\Entity\User;
use App\Security\Voter\PartnerVoter;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserPartnerType extends AbstractType
{
    /**
     * @var array<string, string>
     */
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

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $builder->getData();
        if ($user->getUserPartners()->count() > 1) {
            $this->roles = [
                'Admin. partenaire' => 'ROLE_ADMIN_PARTNER',
                'Agent' => 'ROLE_USER_PARTNER',
            ];
        }
        $role = in_array($user->getRoles()[0], $this->roles) ? $user->getRoles()[0] : 'ROLE_USER_PARTNER';
        if ($user->getId()) {
            $builder->add('email', TextType::class, [
                'required' => false,
                'label' => 'Courriel',
                'help' => 'Un e-mail d\'activation du compte sera envoyé à cette adresse e-mail.',
            ]);
        } else {
            $builder
            ->add('emailDisplay', null, [
                'mapped' => false,
                'disabled' => true,
                'label' => 'Courriel',
                'help' => 'Un e-mail d\'activation du compte sera envoyé à cette adresse e-mail.',
                'data' => $user->getEmail(),
            ])
            ->add('email', HiddenType::class);
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
                $data = $event->getData();
                $form = $event->getForm();
                $form->add('emailDisplay', null, [
                    'mapped' => false,
                    'disabled' => true,
                    'label' => 'Courriel',
                    'help' => 'Un e-mail d\'activation du compte sera envoyé à cette adresse e-mail.',
                    'data' => $data['email'],
                ]);
            });
        }
        $builder->add('nom', null, [
            'required' => false,
        ])
            ->add('prenom', null, [
                'label' => 'Prénom',
                'required' => false,
            ])
            ->add('fonction', null, [
                'label' => 'Fonction (facultatif)',
            ])
            ->add('phone', null, [
                'label' => 'Téléphone professionnel (facultatif)',
                'required' => false,
            ])
            ->add('role', ChoiceType::class, [
                'choices' => $this->roles,
                'label' => 'Rôle',
                'mapped' => false,
                'data' => $role,
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
        if (!$user->getId()) {
            $builder->add('isMailingSummary', ChoiceType::class, [
                'choices' => [
                    'Un e-mail récapitulatif par jour' => true,
                    'Tous les e-mails' => false,
                ],
                'expanded' => true,
                'label' => 'Fréquence de notifications par e-mail',
                'help' => 'Choisissez si ce compte va recevoir un e-mail à chaque nouveauté sur ses signalements ou un e-mail récapitulatif quotidien.',
            ]);
        }
        if (1 === $user->getUserPartners()->count() && $this->security->isGranted(PartnerVoter::ASSIGN_PERMISSION_AFFECTATION, $user->getPartners()->first())) {
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
            'validation_groups' => ['user_partner_mail_multi', 'user_partner'],
        ]);
    }
}
