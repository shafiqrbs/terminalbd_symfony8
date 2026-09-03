<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\Marketing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MarketingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter executive name'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter name')
                ]]
            )
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter designation'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter designation')
                    ]]
            )
            ->add('mobileNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter mobile no'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter mobile no')
                    ]]
            )
             ->add('monthlySales',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','autocomplete'=>'off','placeholder'=>'Monthly sales'],
                    ]
            )
             ->add('yearlySales',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','autocomplete'=>'off','placeholder'=>'Yearly sales'],
                    ])
             ->add('discount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','autocomplete'=>'off','placeholder'=>'Discount Percent'],]
            )
            ->add('joiningDate', DateType::class, [
                'widget' => 'choice',
                'years' => range(date('Y'), date('Y')-50),
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Marketing::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'marketing';
    }


}
