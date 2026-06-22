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
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // provenance/destination dérivent de la ligne (non éditables) ; on ne modifie que la date et le car
        $carChoices = [];
        foreach ($options['cars'] as $c) {
            $label = $c['matricule'] . ' (' . ($c['marque']['libelle'] ?? '-') . ')';
            $carChoices[$label] = $c['id'];
        }

        $builder
            ->add('car', ChoiceType::class, [
                'label' => 'Véhicule (optionnel)',
                'choices' => $carChoices,
                'required' => false
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
            'cars' => []
        ]);
    }
}
