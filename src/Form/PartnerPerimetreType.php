<?php

namespace App\Form;

use App\Entity\Commune;
use App\Entity\Partner;
use App\Entity\Zone;
use App\Form\Type\SearchCheckboxType;
use App\Repository\CommuneRepository;
use App\Repository\ZoneRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PartnerPerimetreType extends AbstractType
{
    public function __construct(
        private readonly CommuneRepository $communeRepository,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Partner $partner */
        $partner = $builder->getData();
        $territory = $partner->getTerritory();

        $builder
            ->add('insee', SearchCheckboxType::class, [
                'class' => Commune::class,
                'label' => 'Commune(s)',
                'query_builder' => function (CommuneRepository $communeRepository) use ($territory) {
                    return $communeRepository->createQueryBuilder('c')
                        ->where('c.territory = :territory')
                        ->andWhere('c.id IN (
                            SELECT MIN(c2.id) FROM '.Commune::class.' c2 
                            WHERE c2.territory = :territory 
                            GROUP BY c2.codeInsee
                        )')
                        ->setParameter('territory', $territory)
                        ->orderBy('c.nom', 'ASC');
                },
                'choice_label' => function (Commune $commune): string {
                    return $commune->getNom(withArrondissement: true);
                },
                'help' => 'Sélectionner la ou la liste des communes d\'intervention',
                'required' => false,
                'noselectionlabel' => 'Sélectionnez les communes',
                'nochoiceslabel' => 'Aucune commune disponible',
            ])->add('zones', SearchCheckboxType::class, [
                'class' => Zone::class,
                'query_builder' => function (ZoneRepository $zoneRepository) use ($territory) {
                    return $zoneRepository->createQueryBuilder('z')
                        ->where('z.territory = :territory')
                        ->setParameter('territory', $territory)
                        ->orderBy('z.name', 'ASC');
                },
                'choice_label' => 'name',
                'label' => 'Zones',
                'help' => 'Sélectionnez les zones à inclure dans la liste',
                'noselectionlabel' => 'Sélectionnez les zones',
                'nochoiceslabel' => 'Aucune zone disponible',
                'by_reference' => false,
            ])->add('excludedZones', SearchCheckboxType::class, [
                'class' => Zone::class,
                'query_builder' => function (ZoneRepository $zoneRepository) use ($territory) {
                    return $zoneRepository->createQueryBuilder('z')
                        ->where('z.territory = :territory')
                        ->setParameter('territory', $territory)
                        ->orderBy('z.name', 'ASC');
                },
                'choice_label' => 'name',
                'label' => 'Zones à exclure',
                'help' => 'Sélectionnez les zones à exclure dans la liste',
                'noselectionlabel' => 'Sélectionnez les zones',
                'nochoiceslabel' => 'Aucune zone disponible',
                'by_reference' => false,
            ])->add('save', SubmitType::class, [
                'label' => 'Valider',
                'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
                'row_attr' => ['class' => 'fr-text--right'],
            ]);

        $builder->get('insee')->addModelTransformer(new CallbackTransformer(
            function (array $arrayOfCodesInsee) {
                return $this->communeRepository->findDistinctCommuneCodesInseeForCodeInseeList($arrayOfCodesInsee);
            },
            function (array $arrayOfCommunes) {
                $codesInsee = [];
                foreach ($arrayOfCommunes as $commune) {
                    $codesInsee[] = $commune->getCodeInsee();
                }

                return $codesInsee;
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
        ]);
    }
}
