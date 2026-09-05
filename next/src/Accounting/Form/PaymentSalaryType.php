<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\Accounting\Entity\SalarySetting;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\PaymentSalary;
use App\User\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PaymentSalaryType extends AbstractType
{

    public  $user;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->user = $options['user'];

        $builder

            ->add('paidAmount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Pay amount']])
            ->add('adjustmentAmount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Adjustment amount']])
            ->add('totalAmount',HiddenType::class)
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Remark']])
            ->add('salaryMonth', ChoiceType::class, [
                'attr'=>['class'=>'span8 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Please choose required')
                ],
                'choices' => ['January' => 'January', 'February' => 'February', 'March' => 'March', 'April' => 'April', 'May' => 'May', 'June' => 'June', 'July' => 'July', 'August' => 'August', 'September' => 'September', 'October' => 'October', 'November' => 'November', 'December' => 'December'],
            ])
            ->add('salaryYear', ChoiceType::class, [
                'attr'=>['class'=>'span4 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Please choose required')
                ],
                'choices' => ['2016' => '2016', '2017' => '2017', '2018' => '2018', '2019' => '2019', '2020' => '2020', '2021' => '2021', '2022' => '2022', '2023' => '2023', '2024' => '2024', '2025' => '2025'],
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a transaction method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2 transactionMethod'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.name")
            ])
            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->user->getGlobalOption()->getId()),
            ])

            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile banking---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->user->getGlobalOption()->getId()),
            ])
            ->add('salarySetting', EntityType::class, [
                'required'    => true,
                'class' => SalarySetting::class,
                'placeholder' => '---Select invoice---',
                'choice_label' => 'invoice',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status  =1")
                    ->andWhere("e.process = 'approved'")
                    ->andWhere("e.process != 'done' ")
                    ->andWhere("e.user  =".$this->user->getId()),
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => PaymentSalary::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_accountingBundle_paymentsalary';
    }
}
