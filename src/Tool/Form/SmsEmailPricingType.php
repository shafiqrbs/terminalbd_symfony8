<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\SmsEmailPricing;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SmsEmailPricingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter package name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('quantity',IntegerType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter package quantity'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter unit price'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('type', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['SMS' => 'SMS', 'Email' => 'Email'],
            ])
            ->add('status')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SmsEmailPricing::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_smsemailpricing';
    }
}
