<?php

namespace App\Form;

use App\Entity\Bailleur;
use App\Entity\Enum\PartnerType as EnumPartnerType;
use App\Entity\Enum\Qualification;
use App\Entity\Partner;
use App\Entity\User;
use App\Form\Type\SearchCheckboxEnumType;
use App\Form\Type\TerritoryChoiceType;
use App\Repository\BailleurRepository;
use App\Repository\TerritoryRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PartnerType extends AbstractType
{
    private bool $isAdmin = false;
    private bool $isAdminTerritory = false;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TerritoryRepository $territoryRepository,
        private readonly Security $security,
        #[Autowire(param: 'competence_per_type')]
        private readonly array $competencePerType,
    ) {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        }
        if ($this->security->isGranted('ROLE_ADMIN_TERRITORY')) {
            $this->isAdminTerritory = true;
        }
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $territory = false;
        /** @var Partner $partner */
        $partner = $builder->getData();
        $territory = $options['data']->getTerritory();

        $builder
            ->add('nom', null, [
                'label' => 'Nom du partenaire',
                'help' => 'Le nom du partenaire sera visible dans les signalements pour les autres partenaires',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail de contact (facultatif)',
                'help' => 'S\'il y a des responsables de territoire au sein du partenaire, cette adresse e-mail sera visible par les agents du territoire.',
                'required' => false,
                'empty_data' => '',
            ])
            ->add('emailNotifiable', ChoiceType::class, [
                'choices' => [
                    'Oui' => true,
                    'Non' => false,
                ],
                'expanded' => true,
                'label' => 'Notifier l\'adresse e-mail de contact ?',
                'help' => 'Est-ce que les e-mails concernant les signalements du partenaire doivent être envoyés à cette adresse ?',
            ])
            ->add('type', EnumType::class, [
                'label' => 'Type de partenaire',
                'help' => 'Sélectionnez un type pour afficher les champs à remplir. Si vous ne trouvez pas de type de partenaire adapté, sélectionnez "Autre".',
                'class' => EnumPartnerType::class,
                'choice_label' => function ($choice) {
                    return $choice->label();
                },
                'choice_attr' => function (EnumPartnerType $choice) {
                    $competences = $this->competencePerType[$choice->name] ?? [];

                    return [
                        'data-competences' => json_encode(array_map(fn (Qualification $q) => $q->value, $competences)),
                    ];
                },
                'placeholder' => 'Sélectionner un type',
                'disabled' => !$this->isAdminTerritory,
                'required' => false,
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
                'help' => 'Choisissez une ou plusieurs compétences parmi la liste ci-dessous.',
                'required' => false,
                'showSelectionAsBadges' => true,
            ])
            ->add('isEsaboraActive', CheckboxType::class, [
                'attr' => [
                    'class' => 'fr-toggle__input',
                ],
                'required' => false,
                'disabled' => !$this->isAdminTerritory,
            ])
            ->add('esaboraUrl', UrlType::class, [
                'required' => false,
                'disabled' => !$this->isAdmin,
                'default_protocol' => null,
            ])
            ->add('esaboraToken', TextType::class, [
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
                'required' => false,
                'default_protocol' => null,
            ]);
        }

        $builder->add('territory', TerritoryChoiceType::class, [
            'data' => $territory,
            'row_attr' => [
                'class' => !$this->isAdmin ? 'fr-hidden' : '',
            ],
            'disabled' => !$this->isAdmin,
        ]);
        $this->addBailleurSocialField($builder, $territory?->getZip(), $partner);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, fn (FormEvent $event) => $this->handleTerritoryChange($event));
        $builder->addEventListener(FormEvents::PRE_SET_DATA, fn (FormEvent $event) => $this->handleTerritoryChange($event, true));
    }

    private function handleTerritoryChange(FormEvent $event, bool $isPreSetData = false): void
    {
        $data = $event->getData();
        $form = $event->getForm();

        $territory = $isPreSetData && $data instanceof Partner
            ? $data->getTerritory()
            : (\array_key_exists('territory', $data) ? $this->territoryRepository->find($data['territory']) : null);

        $this->addBailleurSocialField($form, $territory?->getZip(), $data);
    }

    private function addBailleurSocialField(FormBuilderInterface|FormInterface $builder, ?string $territoryZip = null, mixed $data = null): void
    {
        if (null === $territoryZip) {
            if ($data instanceof Partner && null !== $data->getTerritory()) {
                $territoryZip = $data->getTerritory()->getZip();
            } elseif ($this->isAdmin) {
                $territoryZip = '01';
            } else {
                /** @var User $user */
                $user = $this->security->getUser();
                $territoryZip = $user->getFirstTerritory()->getZip();
            }
        }
        $builder->add('bailleur', EntityType::class, [
            'class' => Bailleur::class,
            'query_builder' => fn (BailleurRepository $bailleurRepository) => $bailleurRepository->getBailleursByTerritoryQueryBuilder($territoryZip),
            'choice_label' => 'name',
            'placeholder' => 'Sélectionner une dénomination officielle pour le bailleur social',
            'required' => false,
            'attr' => ['data-dynamic' => 'bailleur-social'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'constraints' => [
                new Assert\Callback([
                    $this,
                    'validateEmailIsUnique',
                ]),
                new Assert\Callback([
                    $this,
                    'validateEsaboraAndIdoss',
                ]),
            ],
        ]);
    }

    public function validateEmailIsUnique(mixed $value, ExecutionContextInterface $context): void
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

    public function validateEsaboraAndIdoss(mixed $value, ExecutionContextInterface $context): void
    {
        if ($value instanceof Partner) {
            // Esabora
            if ($value->isEsaboraActive()) {
                if (empty($value->getEsaboraUrl()) || empty($value->getEsaboraToken())) {
                    $context->buildViolation('Pour activer Esabora, l’URL et le token doivent être renseignés.')
                        ->atPath('isEsaboraActive')
                        ->addViolation();
                }
            }
            // Idoss
            if (method_exists($value, 'isIdossActive') && $value->isIdossActive()) {
                if (empty($value->getIdossUrl())) {
                    $context->buildViolation('Pour activer Idoss, l’URL doit être renseignée.')
                        ->atPath('isIdossActive')
                        ->addViolation();
                }
            }
        }
    }
}
