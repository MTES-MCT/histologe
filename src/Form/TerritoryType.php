<?php

namespace App\Form;

use App\Entity\Territory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints as Assert;

class TerritoryType extends AbstractType
{
    public function __construct(private readonly UrlGeneratorInterface $urlGenerator)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $territory = $builder->getData();
        $builder->add('zip', null, [
            'label' => 'Code postal',
            'disabled' => true,
        ])
            ->add('name', null, [
                'label' => 'Nom',
                'disabled' => true,
            ])
            ->add('isActive', null, [
                'label' => 'Territoire actif',
                'disabled' => true,
            ])
            ->add('authorizedCodesInsee', null, [
                'label' => 'Codes Insee ouverts',
                'disabled' => true,
            ])
            ->add('timezone', null, [
                'disabled' => true,
            ]);
        $labelGrilleFile = 'Ajouter la grille de visite spécifique au territoire';
        if ($territory->getGrilleVisiteFilename()) {
            $labelGrilleFile = 'Remplacer la grille de visite spécifique au territoire';
        }
        $builder->add('grilleVisite', FileType::class, [
            'mapped' => false,
            'required' => false,
            'label' => $labelGrilleFile,
            'help' => 'Grille de visite du territoire au format PDF',
            'constraints' => [
                new Assert\File([
                    'mimeTypes' => [
                        'application/pdf',
                        'application/x-pdf',
                    ],
                    'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide',
                ]),
            ],
        ]);
        if ($territory->getGrilleVisiteFilename()) {
            $grilleUrl = $this->urlGenerator->generate('back_territory_grille_visite', ['territory' => $territory->getId()]);
            $builder->add('deleteGrilleVisite', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Supprimer la grille de visite spécifique au territoire - <a class="fr-link" href="'.$grilleUrl.'" target="_blank" rel="noreferrer noopener" >Voir la grille</a>',
                'label_html' => true,
                'required' => false,
            ]);
        }
        $builder->add('isGrilleVisiteDisabled', CheckboxType::class, [
            'label' => 'Désactiver la fonctionnalitée grille de visite sur ce territoire',
            'required' => false,
        ]);
        $builder->add('submit', SubmitType::class, [
            'label' => 'Modifier',
            'attr' => ['class' => 'fr-btn fr-icon-check-line fr-btn--icon-left'],
            'row_attr' => ['class' => 'fr-text--right'],
        ]);
        $builder->get('authorizedCodesInsee')->addModelTransformer(new CallbackTransformer(
            function ($tagsAsArray) {
                return implode(',', $tagsAsArray);
            },
            function ($tagsAsString) {
                return explode(',', $tagsAsString);
            }
        ));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Territory::class,
            'csrf_token_id' => 'territory_type',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
