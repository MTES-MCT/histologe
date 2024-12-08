<?php

namespace App\Form;

use App\Entity\Enum\PartnerType as EnumPartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Form\Type\SearchCheckboxEnumType;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PartnerType extends AbstractType
{
    private $isAdmin = false;
    private $isAdminTerritory = false;

    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly UserRepository $userRepository,
        private readonly Security $security,
    ) {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $this->isAdminTerritory = true;
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $territory = false;
        $insee = $this->parameterBag->get('authorized_codes_insee');
        /** @var Partner $partner */
        $partner = $builder->getData();
        $territory = $options['data']->getTerritory();

        $builder
            ->add('nom', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'readonly' => isset($insee[$partner->getTerritory()?->getZip()][$partner->getNom()]),
                ],
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
            ])
            ->add('type', EnumType::class, [
                'class' => EnumPartnerType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'placeholder' => 'Sélectionner un type',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'help' => 'Choisissez un type de partenaire parmi la liste ci-dessous.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'disabled' => !$this->isAdminTerritory,
            ])
            ->add('competence', SearchCheckboxEnumType::class, [
                'class' => Qualification::class,
                'choice_filter' => ChoiceList::filter(
                    $this,
                    function ($choice) {
                        return Qualification::DANGER == $choice ? false : $choice;
                    },
                    'competence'
                ),
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'label' => 'Compétences (facultatif)',
                'noselectionlabel' => 'Sélectionner une ou plusieurs compétences',
                'nochoiceslabel' => 'Aucune compétence disponible',
                'multiple' => true,
                'expanded' => false,
                'help' => 'Choisissez une ou plusieurs compétences parmi la liste ci-dessous.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'required' => false,
            ])
            ->add('isEsaboraActive', CheckboxType::class, [
                'attr' => [
                    'class' => 'fr-toggle__input',
                ],
                'required' => false,
                'disabled' => !$this->isAdminTerritory,
            ])
            ->add('esaboraUrl', UrlType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
                'disabled' => !$this->isAdmin,
            ])
            ->add('esaboraToken', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
                'disabled' => !$this->isAdmin,
            ]);
        if ($this->isAdmin) {
            $builder->add('isIdossActive', CheckboxType::class, [
                'attr' => [
                    'class' => 'fr-toggle__input',
                ],
                'required' => false,
            ])
            ->add('idossUrl', UrlType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
            ]);
        }
        $builder->add('territory', EntityType::class, [
            'class' => Territory::class,
            'query_builder' => function (TerritoryRepository $tr) {
                return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
            },
            'data' => !empty($territory) ? $territory : null,
            'disabled' => !$this->isAdmin,
            'choice_label' => function (Territory $territory) {
                return $territory->getZip().' - '.$territory->getName();
            },
            'attr' => [
                'class' => 'fr-select',
            ],
            'row_attr' => [
                'class' => !$this->isAdmin ? 'fr-input-group fr-hidden' : 'fr-input-group',
            ],
            'label' => 'Territoire',
            'required' => true,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'constraints' => [
                new Assert\Callback([$this, 'validatePartnerCanBeNotified']),
                new Assert\Callback([$this, 'validateEmailIsUnique']),
            ],
        ]);
    }

    public function validateEmailIsUnique(mixed $value, ExecutionContextInterface $context)
    {
        if ($value instanceof Partner) {
            $partner = $value;

            if (empty($partner->getEmail())) {
                return;
            }
            /** @var ?User $user */
            $user = $this->userRepository->findOneBy(['email' => $partner->getEmail()]);

            if (!empty($user) && !$user->isUsager()) {
                $context->addViolation('Un utilisateur existe déjà avec cette adresse e-mail.');
            }
        }
    }

    public function validatePartnerCanBeNotified(mixed $value, ExecutionContextInterface $context)
    {
        if ($value instanceof Partner) {
            $partner = $value;
            $usersActive = $partner->getUsers()->filter(function (User $user) {
                return User::STATUS_ACTIVE === $user->getStatut();
            });

            if (!empty($partner->getEmail()) || $usersActive->isEmpty()) {
                return;
            }

            $canBeNotified = $usersActive->exists(function (int $i, User $user) {
                return $user->getIsMailingActive();
            });

            if (!$canBeNotified) {
                $context->addViolation('E-mail générique manquante: Il faut donc obligatoirement qu\'au moins
                1 compte utilisateur accepte de recevoir les e-mails.');
            }
        }
    }
}
