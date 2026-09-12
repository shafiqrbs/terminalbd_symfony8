<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountCondition;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountConditionType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Customer name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter account name')
                    ]]
            )
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','autocomplete'=>'off','placeholder'=>'Mobile no'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter mobile no')
                    ]]
            )
            ->add('address',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 ','rows'=>4,'placeholder'=>'Enter customer address']])
            ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountCondition::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'condition';
    }

}
