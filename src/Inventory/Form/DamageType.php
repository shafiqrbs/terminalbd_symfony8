<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Damage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DamageType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('purchaseItem',TextType::class, ['attr'=>['class'=>'m-wrap span12'],
                'constraints' =>[
                    new NotBlank(message: 'Please add  size name')
            ]])
            ->add('quantity',IntegerType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Quantity'],
                'constraints' =>[
                    new NotBlank(message: 'Please add  quantity')
            ]])
            ->add('notes',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Notes...']]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Damage::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_Damage';
    }
}
