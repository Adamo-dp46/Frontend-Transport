<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TarifFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('libelle', TextType::class, [
                'label' => 'Libellé',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length(max: 150)
                ]
            ])
            ->add('montant', NumberType::class, [
                'label' => 'Montant (FCFA)',
                'scale' => 0,
                'constraints' => [
                    new Assert\NotNull(),
                    new Assert\Positive()
                ],
                'attr' => [
                    'placeholder' => '0'
                ]
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'tarif'
        ]);
    }
}
