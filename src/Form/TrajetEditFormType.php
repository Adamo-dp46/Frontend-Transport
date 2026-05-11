<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class TrajetEditFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /*
            $gareChoices = [];
            foreach ($options['gares'] as $g) {
                $label = $g['libelle'] . ' — ' . ($g['ville'] ?? '');
                $gareChoices[$label] = $g['id'];
            }
        */
        $tarifChoices = [];
        foreach($options['tarifs'] as $t) {
            $label = ($t['libelle'] ?? 'Tarif') . ' - ' . number_format($t['montant'] ?? 0, 0, ',', ' ') . ' FCFA';
            $tarifChoices[$label] = $t['id'];
        }

        $builder
            /*
                ->add('provenance', ChoiceType::class, [
                    'label' => 'Gare de départ',
                    'choices' => $gareChoices,
                    'placeholder' => '— Sélectionner —',
                    'constraints' => [new NotNull()]
                ])
                ->add('destination', ChoiceType::class, [
                    'label' => 'Gare d\'arrivée',
                    'choices' => $gareChoices,
                    'placeholder' => '— Sélectionner —',
                    'constraints' => [new NotNull()]
                ])
            */
            ->add('tarifId', ChoiceType::class, [
                'label' => 'Tarif',
                'choices' => $tarifChoices,
                'placeholder' => '— Sélectionner un tarif —',
                'constraints' => [new NotNull()]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'trajetedit',
            // 'gares' => [],
            'tarifs' => []
        ]);
    }
}
