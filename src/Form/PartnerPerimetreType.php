<?php

namespace App\Form;

use App\Entity\Commune;
use App\Entity\Partner;
use App\Entity\Zone;
use App\Form\Type\SearchCheckboxType;
use App\Manager\CommuneManager;
use App\Repository\ZoneRepository;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class PartnerPerimetreType extends AbstractType
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
        private readonly CommuneManager $communeManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $territory = false;
        $insee = $this->parameterBag->get('authorized_codes_insee');
        /** @var Partner $partner */
        $partner = $builder->getData();
        $territory = $partner->getTerritory();

        $builder
            ->add('insee', TextType::class, [
                'label' => 'Code(s) INSEE',
                'help' => 'Renseignez le ou les codes INSEE, séparés par une virgule. Exemple: 67001, 67002, 67003.',
                'attr' => [
                    'class' => 'fr-input',
                    'readonly' => isset($insee[$territory?->getZip()][$partner->getNom()]),
                ],
                'required' => false,
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
                'label' => 'Modifier',
                'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
                'row_attr' => ['class' => 'fr-text--right'],
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Partner::class,
            'constraints' => [
                new Assert\Callback([$this, 'validateInseeInTerritory']),
            ],
        ]);
    }

    public function validateInseeInTerritory(mixed $value, ExecutionContextInterface $context)
    {
        if ($value instanceof Partner) {
            $partner = $value;
            $codesInsee = $partner->getInsee();
            if (empty($codesInsee)) {
                return;
            }
            $territory = $partner->getTerritory();
            foreach ($codesInsee as $insee) {
                /** @var ?Commune $commune */
                $commune = $this->communeManager->findOneBy(['codeInsee' => trim($insee)]);
                if (null === $commune) {
                    $context->addViolation('Il n\'existe pas de commune avec le code insee '.$insee);
                } elseif ($commune->getTerritory() !== $territory) {
                    $context->addViolation('La commune avec le code insee '.$insee.' ne fait pas partie du territoire du partenaire');
                }
            }
        }
    }
}
