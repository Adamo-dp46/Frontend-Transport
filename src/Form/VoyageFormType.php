<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class VoyageFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $ligneChoices = [];
        foreach ($options['lignes'] as $l) {
            $label = $l['libelle']
                ?? (($l['gareorigine']['libelle'] ?? '?') . ' → ' . ($l['gareterminus']['libelle'] ?? '?'));
            $label .= ' (' . ($l['codeligne'] ?? '') . ')';
            $ligneChoices[$label] = $l['id'];
        }

        $carChoices = [];
        foreach($options['cars'] as $c) {
            $label = $c['matricule'] . ' (' . ($c['marque']['libelle'] ?? '-') . ')';
            $carChoices[$label] = $c['id'];
        }

        $builder
            ->add('ligne', ChoiceType::class, [
                'label' => 'Ligne',
                'choices' => $ligneChoices,
                'placeholder' => '-- Sélectionner une ligne --',
                'constraints' => [new Assert\NotNull()]
            ])
            ->add('datedebut', DateTimeType::class, [
                'label' => 'Date et heure de départ',
                'widget' => 'single_text',
                'constraints' => [new Assert\NotNull()]
            ])
            ->add('car', ChoiceType::class, [
                'label' => 'Car',
                'choices' => [
                    '-- Aucun car (optionnel) --' => null,
                ] + $carChoices,
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'voyage',
            'lignes' => [],
            'cars' => []
        ]);
    }
}
