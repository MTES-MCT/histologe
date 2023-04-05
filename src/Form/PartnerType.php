<?php

namespace App\Form;

use App\Entity\Commune;
use App\Entity\Enum\PartnerType as EnumPartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Manager\CommuneManager;
use App\Manager\UserManager;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PartnerType extends AbstractType
{
    public function __construct(
        private UserManager $userManager,
        private UserFactory $userFactory,
        private ParameterBagInterface $parameterBag,
        private CommuneManager $communeManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $territory = false;
        $insee = $this->parameterBag->get('authorized_codes_insee');
        /** @var Partner $partner */
        $partner = $builder->getData();
        if ($options['territory']) {
            $territory = $options['territory'];
        } else {
            $territory = $options['data']->getTerritory();
        }

        $builder
            ->add('nom', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'readonly' => isset($insee[$partner?->getTerritory()?->getZip()][$partner->getNom()]),
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
            ])
            ->add('competence', EnumType::class, [
                'class' => Qualification::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'row_attr' => [
                    'class' => 'fr-select-group',
                ],
                'placeholder' => 'Sélectionner une ou plusieurs compétences',
                'multiple' => true,
                'expanded' => false,
                'attr' => [
                    'class' => 'fr-select',
                ],
                'help' => 'Choisissez une ou plusieurs compétences parmi la liste ci-dessous.',
                'help_attr' => [
                    'class' => 'fr-hint-text',
                ],
                'required' => false,
            ])
            ->add('insee', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'readonly' => isset($insee[$partner?->getTerritory()?->getZip()][$partner->getNom()]),
                ],
                'required' => false,
            ])
            ->add('zones_pdl', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                    'readonly' => isset($insee[$partner?->getTerritory()?->getZip()][$partner->getNom()]),
                ],
                'required' => false,
                'mapped' => false,
            ])
            ->add('esaboraUrl', UrlType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
            ])
            ->add('esaboraToken', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
            ])
            ->add('territory', EntityType::class, [
                'class' => Territory::class,
                'query_builder' => function (TerritoryRepository $tr) {
                    return $tr->createQueryBuilder('t')->andWhere('t.isActive = 1')->orderBy('t.id', 'ASC');
                },
                'data' => !empty($territory) ? $territory : null,
                'disabled' => !$options['can_edit_territory'],
                'choice_label' => 'name',
                'attr' => [
                    'class' => 'fr-select',
                ],
                'row_attr' => [
                    'class' => 'fr-input-group',
                ],
                'label' => 'Territoire',
                'required' => true,
            ]);
        $builder->get('insee')->addModelTransformer(new CallbackTransformer(
            function ($tagsAsArray) {
                // transform the array to a string
                return implode(',', $tagsAsArray);
            },
            function ($tagsAsString) {
                // transform the string back to an array
                $pattern = '/(\s*,*\s*)*,+(\s*,*\s*)*/';

                return null !== $tagsAsString ? preg_split($pattern, $tagsAsString, -1, \PREG_SPLIT_NO_EMPTY) : [];
            }
        ));

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Partner $partner */
            $partner = $event->getData();
            $form = $event->getForm();

            if ($form->get('zones_pdl')?->getData()) {
                $zonesPdlList = explode(',', $form->get('zones_pdl')?->getData());
                // TODO : vérifier que le code insee correspond au territoire avant de modifier la commune ?
                foreach ($zonesPdlList as $zonePdl) {
                    /** @var Commune $commune */
                    $commune = $this->communeManager->findOneBy(['codeInsee' => trim($zonePdl)]);
                    if ($commune) {
                        $commune->setIsZonePermisLouer(true);
                    }
                }
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'allow_extra_fields' => true,
            'territory' => null,
            'route' => null,
            'can_edit_territory' => true,
            'constraints' => [
                new Assert\Callback([$this, 'validatePartnerCanBeNotified']),
            ],
            // TODO : ajouter une contrainte pour vérifier que les codes insee correspondent aux territoires ?
        ]);
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
                $context->addViolation('Email générique manquante: Il faut donc obligatoirement qu\'au moins
                1 compte utilisateur accepte de recevoir les emails.');
            }
        }
    }
}
