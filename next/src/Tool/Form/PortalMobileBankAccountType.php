<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\PortalMobileBankAccount;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PortalMobileBankAccountType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric mobile','placeholder'=>'add payment receive mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'add payment receive mobile no')
                ]])
            ->add('authorised',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'add authorised company'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
            ])
            ->add('serviceName', ChoiceType::class, [
                'attr'=>['class'=>'span12 select2'],
                'choices' => [
                    'Bkash' => 'Bkash',
                    'Rocket' => 'Rocket',
                ],
            ])
            ->add('accountOwner',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'add account ownership'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
            ])
            ->add('accountType', ChoiceType::class, [
                'attr'=>['class'=>'span12 select2'],
                'choices' => [
                    'Personal' => 'Personal',
                    'General' => 'General',
                    'Merchant' => 'Merchant',
                ],
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PortalMobileBankAccount::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_portalmobilebankaccount';
    }
}
