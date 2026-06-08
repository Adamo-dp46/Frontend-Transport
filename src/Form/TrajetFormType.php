<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TrajetFormType extends AbstractType
{
    private function buildGareChoices(array $gares): array
    {
        // Label affiché : "Nom de la gare — Ville"
        // Valeur soumise : id de la gare (le controller résout en libellé avant POST)
        $choices = [];
        foreach ($gares as $g) {
            $label = $g['libelle'] . ' - ' . ($g['ville'] ?? '');
            $choices[$label] = $g['id'];
        }
        return $choices;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $gareChoices = $this->buildGareChoices($options['gares']);

        $tarifChoices = [];
        /* -- Vu qu'on n'a pas encore 'libelle'
            foreach($options['tarifs'] as $t) {
                $label = $t['libelle'] . ' — ' . number_format($t['montant'], 0, ',', ' ') . ' FCFA';
                $tarifChoices[$label] = $t['id'];
            }
        */
        foreach($options['tarifs'] as $t) {
            $label = $t['libelle'] . ' - ' . number_format($t['montant'], 0, ',', ' ') . ' FCFA';
            $tarifChoices[$label] = $t['id'];
        }
        /*
            $carChoices = array_merge(
                ['-- Aucun car (optionnel) --' => null],
                array_combine(
                    array_column($options['cars'], 'matricule'),
                    array_column($options['cars'], 'id')
                )
            ); -- Ou..
            $carChoices = [];
            foreach ($options['cars'] as $c) {
                $label = $c['matricule'] . ' (' . ($c['marque']['libelle'] ?? '—') . ')';
                $carChoices[$label] = $c['id'];
            }
        */
        $builder
            ->add('provenance', ChoiceType::class, [
                'label' => 'Provenance',
                'choices' => $gareChoices,
                'placeholder' => '-- Sélectionner une gare de départ --',
                'constraints' => [
                    new Assert\NotNull()
                ]
            ])
            ->add('destination', ChoiceType::class, [
                'label' => 'Destination',
                'choices' => $gareChoices,
                'placeholder' => '-- Sélectionner une gare d\'arrivée --',
                'constraints' => [
                    new Assert\NotNull()
                ]
            ])
            /*
                ->add('destination', ChoiceType::class, [
                    'label' => 'Destination',
                    'choices' => [],
                    'attr' => [
                        'data-remote-select' => 'gares',
                        'data-value' => $options['current_id'] ?? '',
                        'data-label' => $options['current_label'] ?? '',
                        'placeholder' => '-- Rechercher un car --'
                    ],
                ])
            */
            ->add('tarifId', ChoiceType::class, [
                'label' => 'Tarif',
                'choices' => $tarifChoices,
                'placeholder' => '-- Sélectionner un tarif --',
                'constraints' => [
                    new Assert\NotNull()
                ]
            ])
            /*
                ->add('datedebut', DateTimeType::class, [
                    'label' => 'Date de départ',
                    'widget' => 'single_text'
                ])
                ->add('carId', ChoiceType::class, [
                    'label' => 'Car',
                    'choices' => $carChoices,
                    'required' => false,
                    'placeholder' => '-- Sélectionner un car --',
                ])
            */
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'trajet',
            'gares' => [],
            'tarifs' => [],
            // 'cars' => []
        ]);
    }
}
