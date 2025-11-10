<?php

namespace App\Form;

use App\Entity\Partner;
use App\Entity\Territory;
use App\Form\Type\SearchCheckboxType;
use App\Form\Type\TerritoryChoiceType;
use App\Repository\PartnerRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchAnnuaireAgentType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('queryUser', SearchType::class, [
            'required' => false,
            'label' => 'Agent',
            'attr' => ['placeholder' => 'Taper le nom ou l\'e-mail d\'un agent'],
        ]);
        $builder->add('page', HiddenType::class);
        $builder->add('territory', TerritoryChoiceType::class);
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($builder) {
            $this->addPartnersField($event->getForm(), $builder->getData()->getTerritory());
        });
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $this->addPartnersField($event->getForm(), isset($event->getData()['territory']) ? $event->getData()['territory'] : null);
        });
        $builder->add('orderType', ChoiceType::class, [
            'choices' => [
                'Ordre alphabétique (A -> Z)' => 'u.nom-ASC',
                'Ordre alphabétique inversé (Z -> A)' => 'u.nom-DESC',
                'Partenaire (A -> Z)' => 'p.nom-ASC',
                'Partenaire inversé (Z -> A)' => 'p.nom-DESC',
            ],
            'required' => false,
            'placeholder' => false,
            'label' => 'Trier par',
            'data' => 'u.nom-ASC',
        ]);
    }

    private function addPartnersField(FormInterface $builder, string|Territory|null $territory): void
    {
        $builder->add('partners', SearchCheckboxType::class, [
            'class' => Partner::class,
            'query_builder' => function (PartnerRepository $partnerRepository) use ($territory) {
                $query = $partnerRepository->createQueryBuilder('p')
                    ->where('p.territory = :territory')
                    ->setParameter('territory', $territory);
                $query->orderBy('p.nom', 'ASC');

                return $query;
            },
            'choice_label' => 'nom',
            'label' => 'Partenaire',
            'noselectionlabel' => 'Tous les partenaires',
            'required' => false,
            'nochoiceslabel' => !$territory ? 'Sélectionner un territoire pour afficher les partenaires disponibles' : 'Aucun partenaire disponible',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
            'attr' => ['class' => 'fr-p-4v bo-filter-form'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
