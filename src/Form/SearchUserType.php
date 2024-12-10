<?php

namespace App\Form;

use App\Entity\Enum\PartnerType;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Form\Type\SearchCheckboxType;
use App\Repository\PartnerRepository;
use App\Service\SearchUser;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchUserType extends AbstractType
{
    private const PERMISSION_CHOICES = [
        'Oui' => 'Oui',
        'Non' => 'Non',
    ];

    private bool $isAdmin = false;
    private array $roleChoices = [];

    public function __construct(
        private readonly Security $security,
        private readonly ParameterBagInterface $parameterBag,
    ) {
        $this->roleChoices = User::ROLES;
        unset($this->roleChoices['Usager']);
        if ($this->security->isGranted('ROLE_ADMIN')) {
            $this->isAdmin = true;
        } else {
            unset($this->roleChoices['Super Admin']);
        }
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryUser', SearchType::class, [
            'required' => false,
            'label' => false,
            'attr' => ['placeholder' => 'Taper le nom ou l\'e-mail d\'un utilisateur'],
        ]);
        if ($this->isAdmin) {
            $builder->add('territory', EntityType::class, [
                'class' => Territory::class,
                'choice_label' => function (Territory $territory) {
                    return $territory->getZip().' - '.$territory->getName();
                },
                'required' => false,
                'placeholder' => 'Tous les territoires',
                'label' => false,
            ]);
        }
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder) {
            $this->addPartnersField(
                $event->getForm(),
                $builder->getData()->getTerritory(),
                $builder->getData()->getPartnerType()
            );
            $this->desactivePartnerType($event->getForm(), $builder->getData()->getPartners());
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            if ($this->isAdmin && isset($event->getData()['territory'])) {
                $this->addPartnersField(
                    $event->getForm(),
                    $event->getData()['territory'],
                    isset($event->getData()['partnerType']) ? $event->getData()['partnerType'] : null
                );
            }
            $this->desactivePartnerType(
                $event->getForm(),
                isset($event->getData()['partners']) ? $event->getData()['partners'] : null
            );
        });
        $builder->add('statut', ChoiceType::class, [
            'choices' => [
                'Activé' => 1,
                'Non activé' => 0,
            ],
            'required' => false,
            'placeholder' => 'Statut',
            'label' => false,
        ]);
        $builder->add('role', ChoiceType::class, [
            'choices' => $this->roleChoices,
            'required' => false,
            'placeholder' => 'Rôle',
            'label' => false,
        ]);
        if ($this->parameterBag->get('feature_permission_affectation')) {
            $builder->add('permissionAffectation', ChoiceType::class, [
                'choices' => self::PERMISSION_CHOICES,
                'required' => false,
                'placeholder' => 'Droit d\'affectation',
                'label' => false,
            ]);
        }

        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A -> Z)' => 'u.nom-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 'u.nom-DESC',
                'Partenaire (A -> Z)' => 'p.nom-ASC',
                'Partenaire inversé (Z -> A)' => 'p.nom-DESC',
                'Connexion la plus récente' => 'u.lastLoginAt-ASC',
                'Connexion la plus ancienne' => 'u.lastLoginAt-DESC',
            ],
            'required' => false,
            'label' => 'Trier par',
            'data' => 'u.nom-ASC',
        ]);

        $builder->add('page', HiddenType::class);
    }

    private function addPartnersField(FormInterface $builder, $territory, $partnerType): void
    {
        $builder->add('partners', SearchCheckboxType::class, [
            'class' => Partner::class,
            'query_builder' => function (PartnerRepository $partnerRepository) use ($territory, $partnerType) {
                $query = $partnerRepository->createQueryBuilder('p')
                    ->where('p.territory = :territory')
                    ->setParameter('territory', $territory);
                if (null !== $partnerType && '' !== $partnerType) {
                    $query->andWhere('p.type = :partnerType')
                    ->setParameter('partnerType', $partnerType);
                }
                $query->orderBy('p.nom', 'ASC');

                return $query;
            },
            'choice_label' => 'nom',
            'label' => false,
            'noselectionlabel' => 'Tous les partenaires',
            'nochoiceslabel' => !$territory ? 'Sélectionner un territoire pour afficher les partenaires disponibles' : 'Aucun partenaire disponible',
        ]);
    }

    private function desactivePartnerType(FormInterface $builder, $partners): void
    {
        $options = [
            'class' => PartnerType::class,
            'choice_label' => function ($choice) {
                return $choice->label();
            },
            'row_attr' => [
                'class' => 'fr-select-group',
            ],
            'placeholder' => 'Type de partenaire',
            'attr' => [
                'class' => 'fr-select',
                'disabled' => isset($partners) && !empty($partners) && \count($partners) > 0,
            ],
            'label' => false,
        ];

        $builder->add('partnerType', EnumType::class, $options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SearchUser::class,
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['id' => 'search-user-form', 'class' => 'fr-p-4v'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
