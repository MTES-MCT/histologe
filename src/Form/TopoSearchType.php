<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<mixed>
 */
class TopoSearchType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('code_dep', HiddenType::class)
            ->add('code_commune', HiddenType::class)
            ->add('ban_id', HiddenType::class)
            ->add('submit_topo', SubmitType::class, [
                'label' => 'Rechercher dans TOPO DGFiP',
            ]);

        $this->addLibelleField($builder, $options['data'] ?? $builder->getData());

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $this->addLibelleField($event->getForm(), $data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'csrf_protection' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'topo_search';
    }

    /**
     * @param FormBuilderInterface<mixed>|FormInterface<mixed> $target
     */
    private function addLibelleField(FormBuilderInterface|FormInterface $target, mixed $data): void
    {
        $banId = \is_array($data) ? ($data['ban_id'] ?? null) : null;

        $help = null;
        if ($banId) {
            $help = sprintf(
                'Pour vous aider à retrouver le libellé exact, vous pouvez consulter la fiche BAN correspondante : <a href="https://adresse.data.gouv.fr/carte-base-adresse-nationale?id=%s" target="_blank" rel="noopener" title="adresse.data.gouv.fr - Ouvre une nouvelle fenêtre">voir sur adresse.data.gouv.fr</a>',
                $banId
            );
        }

        $target->add('libelle', TextType::class, [
            'label' => 'Libellé de la voie',
            'required' => true,
            'attr' => ['placeholder' => 'Ex: LOUBRETTE'],
            'help' => $help,
            'help_html' => true,
        ]);
    }
}
