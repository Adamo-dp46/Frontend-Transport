<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class VoyageEditFormType extends AbstractType
{
    private function buildGareChoices(array $gares): array
    {
        $choices = [];
        foreach($gares as $g) {
            $label = $g['libelle'] . ' - ' . ($g['ville'] ?? '');
            $choices[$label] = $g['id'];
        }
        return $choices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $gareChoices = $this->buildGareChoices($options['gares']);
        $carChoices = [];
        foreach ($options['cars'] as $c) {
            $label = $c['matricule'] . ' (' . ($c['marque']['libelle'] ?? '-') . ')';
            $carChoices[$label] = $c['id'];
        }

        $builder
            ->add('car', ChoiceType::class, [
                'label' => 'Véhicule retourné (optionnel)',
                'choices' => $carChoices,
                'required' => false
            ])
            ->add('provenance', ChoiceType::class, [
                'label' => 'Gare de départ',
                'choices' => $gareChoices,
                'placeholder' => '-- Sélectionner une gare de départ --',
                'constraints' => [new NotNull()]
            ])
            ->add('destination', ChoiceType::class, [
                'label' => 'Gare d\'arrivée',
                'choices' => $gareChoices,
                'placeholder' => '-- Sélectionner une gare d\'arrivée --',
                'constraints' => [new NotNull()]
            ])
            ->add('datedebut', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
                'constraints' => [new NotNull()]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'voyageedit',
            'cars' => [],
            'gares' => []
        ]);
    }
}
