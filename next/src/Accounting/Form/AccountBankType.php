<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Tool\Entity\Bank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountBank;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountBankType extends AbstractType
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

            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'add bank address']])
            ->add('isDefault')

            ->add('accountNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'add account no'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
            ])
            ->add('accountOwner',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'add account ownership'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
            ])
            ->add('branch',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'add branch name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
            ])
            ->add('serviceName',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Service name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
            ])
            ->add('serviceCharge',TextType::class, ['attr'=>['class'=>'m-wrap span12','maxLength' => 5 ,'placeholder'=>'Service charge']
             ,'constraints' =>[new NotBlank(message: 'Please input required')],
            ])
            ->add('file')
            ->add('accountType', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => [
                    'Savings' => 'Savings',
                    'Current' => 'Current',
                    'Other' => 'Other'
                ],
            ])
            ->add('method', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'empty_value' => '---Choose a method---',
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'property' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->orderBy("b.name", "ASC"),
            ])->add('bank', EntityType::class, [
                'required'    => true,
                'class' => Bank::class,
                'empty_value' => '---Choose a bank---',
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'property' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->orderBy("b.name", "ASC"),
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountBank::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'accountBank';
    }


}
