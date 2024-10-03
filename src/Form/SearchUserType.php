<?php

namespace App\Form;

use App\Dto\SearchUser;
use App\Entity\Partner;
use App\Entity\Territory;
use App\Entity\User;
use App\Form\Type\SearchCheckboxType;
use App\Repository\PartnerRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchUserType extends AbstractType
{
    private bool $isAdmin = false;
    private array $roleChoices = [];

    public function __construct(
        private readonly Security $security
    ) {
        $this->roleChoices = User::ROLESV2;
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
            $this->addPartnersField($event->getForm(), $builder->getData()->getTerritory());
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            if($this->isAdmin && isset($event->getData()['territory'])) {
                $this->addPartnersField($event->getForm(), $event->getData()['territory']);
            }
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
        $builder->add('page', HiddenType::class);
    }

    private function addPartnersField(FormInterface $builder, $territory): void
    {
        $builder->add('partners', SearchCheckboxType::class, [
            'class' => Partner::class,
            'query_builder' => function (PartnerRepository $partnerRepository) use ($territory) {
                return $partnerRepository->createQueryBuilder('p')
                    ->where('p.territory = :territory')
                    ->setParameter('territory', $territory)
                    ->orderBy('p.nom', 'ASC');
            },
            'choice_label' => 'nom',
            'label' => false,
            'noselectionlabel' => 'Tous les partenaires',
            'nochoiceslabel' => !$territory ? 'Sélectionner un territoire pour afficher les partenaires disponibles' : 'Aucun partenaire disponible',
        ]);
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
