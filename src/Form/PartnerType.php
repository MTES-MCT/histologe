<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Factory\UserFactory;
use App\Manager\UserManager;
use App\Repository\TerritoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
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
    public function __construct(private UserManager $userManager, private UserFactory $userFactory)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $territory = false;
        if ($options['territory']) {
            $territory = $options['territory'];
        } else {
            $territory = $options['data']->getTerritory();
        }

        $builder
            ->add('nom', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
            ])
            ->add('isCommune', ChoiceType::class, [
                'row_attr' => [
                    'class' => 'fr-select-group',
                ], 'attr' => [
                    'class' => 'fr-select',
                ],
                'choices' => [
                    'Commune' => 1,
                    'Partenaire' => 0,
                ],
                'label_attr' => [
                    'class' => 'fr-label',
                ],
                'label' => 'Type de partenaire',
            ])
            ->add('insee', TextType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'attr' => [
                    'class' => 'fr-input',
                ],
                'required' => false,
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
                    return $tr->createQueryBuilder('t')->orderBy('t.id', 'ASC');
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
                return explode(',', $tagsAsString);
            }
        ));

        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event) {
            /** @var Partner $partner */
            $partner = $event->getData();
            $form = $event->getForm();

            if (\array_key_exists('users', $form->getExtraData())) {
                $userList = $form->getExtraData()['users'];
                foreach ($userList as $userId => $userData) {
                    if ('new' !== $userId) {
                        $partner->getUsers()->filter(function (User $user) use ($userId, $userData) {
                            if ($user->getId() === $userId) {
                                return $this->userManager->updateUserFromData($user, $userData);
                            }
                        });
                    } else {
                        foreach ($userData as $userDataItem) {
                            /** @var User $user */
                            $user = $this->userManager->findOneBy(['email' => $userDataItem['email']]);
                            if (null === $user) {
                                $user = $this->userFactory->createInstanceFromArray($partner, $userDataItem);
                                $partner->addUser($user);
                            }
                        }
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
                new Assert\Callback([$this, 'validateUserEmailList']),
                new Assert\Callback([$this, 'validatePartnerCanBeNotified']),
            ],
        ]);
    }

    public function validateUserEmailList(mixed $value, ExecutionContextInterface $context)
    {
        if ($value instanceof Partner) {
            $usersEmail = array_map(function ($user) {
                /* @var User $user */
                return $user->getEmail();
            }, $value->getUsers()->toArray());
            $uniqueUsersEmail = array_unique($usersEmail);

            $conflictsEmail = array_diff_assoc($usersEmail, $uniqueUsersEmail);
            if (\count($conflictsEmail) > 0) {
                $context->addViolation('Les addresses emails doivent être unique.');
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
                $context->addViolation('Email générique manquante: Il faut donc obligatoirement qu\'au moins
                1 compte utilisateur accepte de recevoir les emails.');
            }
        }
    }
}
