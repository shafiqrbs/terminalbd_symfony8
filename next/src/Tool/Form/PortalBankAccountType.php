<?php

namespace App\Tool\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Bank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\PortalBankAccount;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PortalBankAccountType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bank', EntityType::class, [
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'required'    => true,
                'class' => Bank::class,
                'empty_value' => '---Select Bank ---',
                'property' => 'name',
                'attr'=>['class'=>'select2 span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->orderBy('wt.name','ASC'),
            ])
            ->add('contactPerson',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter contact person'],
                'constraints' =>[
                    new NotBlank(message: 'Enter contact person')
                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Enter mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Enter mobile no')
                ]
            ])
            ->add('branch',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter branch name'],
                'constraints' =>[
                    new NotBlank(message: 'Enter branch name')
                ]
            ])
            ->add('accountNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter account no'],
                'constraints' =>[
                    new NotBlank(message: 'Enter account no')
                ]
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PortalBankAccount::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_portalbankaccount';
    }
}
