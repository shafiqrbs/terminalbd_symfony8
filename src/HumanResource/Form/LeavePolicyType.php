<?php

namespace App\HumanResource\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\HumanResource\Entity\LeavePolicy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class LeavePolicyType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter policy name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
             ->add('shortcode',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter short code'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('paymentable')
            ->add('status')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => LeavePolicy::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'leavepolicy';
    }
}
