<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountMobileBank;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountMobileBankType extends AbstractType
{

    public  $globalOption;

    public function __construct(GlobalOption $globalOption)
    {
        $this->globalOption = $globalOption;

    }

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
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => [
                    'Bkash'         => 'Bkash',
                    'Rocket'        => 'Rocket',
                    'Nexus'         => 'Nexus',
                    'iPay'          => 'iPay',
                    'Nagad'         => 'Nagad',
                    'Ucash'         => 'Ucash',
                    'City Touch'    => 'City Touch',
                ],
            ])
            ->add('accountOwner',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'add account ownership'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
            ])
            ->add('serviceCharge',TextType::class, ['attr'=>['class'=>'m-wrap span12','maxLength' => 5 ,'placeholder'=>'Service charge']
            ,'constraints' =>[new NotBlank(message: 'Please input required')],
            ])
            ->add('accountType', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => [
                    'Personal' => 'Personal',
                    'General' => 'General',
                    'Merchant' => 'Merchant',
                ],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountMobileBank::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_accountingbundle_AccountBank';
    }


}
