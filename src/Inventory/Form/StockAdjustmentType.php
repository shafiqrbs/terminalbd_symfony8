<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\ItemStockAdjustment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class StockAdjustmentType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('item',TextType::class, ['attr'=>['class'=>'m-wrap span12 selectAllItem input','placeholder'=>'Enter stock name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('mode', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap discount-type span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Minus' => 'Minus', 'Plus' => 'Plus'],
            ])
            ->add('quantity',NumberType::class, ['attr'=>['class'=>'m-wrap span12 form-control input-number input','placeholder'=>'Quantity']])
            ->add('bonus',NumberType::class, ['attr'=>['class'=>'m-wrap span12 form-control input-number input','placeholder'=>'Bonus']])
            ->add('notes',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter notes ']])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ItemStockAdjustment::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'adjustment';
    }
}
