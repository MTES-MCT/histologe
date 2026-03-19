<?php

namespace App\Form;

use App\Entity\Behaviour\EntityHistoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SearchHistoryEntryType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('entity_name', ChoiceType::class, [
                'label' => 'Type d\'entité',
                'required' => true,
                'choices' => $this->getEntityChoices(),
            ])
            ->add('entity_id', TextType::class, [
                'label' => 'Id',
                'required' => true,
            ])
            ->add('orderType', ChoiceType::class, [
                'label' => 'Ordre',
                'required' => true,
                'choices' => [
                    'Chronologique' => 'ASC',
                    'Antéchronologique' => 'DESC',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Rechercher',
                'attr' => ['class' => 'fr-btn fr-btn--primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => false,
            'method' => 'GET',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }

    /** @return array<string, string> */
    private function getEntityChoices(): array
    {
        $choices = [];

        foreach ($this->entityManager->getMetadataFactory()->getAllMetadata() as $metadata) {
            $className = $metadata->getName();
            if (is_subclass_of($className, EntityHistoryInterface::class)) {
                $shortName = (new \ReflectionClass($className))->getShortName();
                $choices[$shortName] = $shortName;
            }
        }

        ksort($choices);

        return $choices;
    }
}
