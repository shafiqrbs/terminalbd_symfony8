<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\DomainUser\Entity\Customer;
use App\Accounting\Entity\AccountBank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountSales;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountSalesBusinessType extends AbstractType
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

            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Add receive amount'],
                'constraints' =>[
                    new NotBlank(message: 'Add receive amount')
                ]])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add Remark']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'property' => 'name',
                'empty_value' => '---Choose a method---',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])
            ->add('customer', EntityType::class, [
                'required'    => true,
                'class' => Customer::class,
                'empty_value' => '---Choose a customer---',
                'property' => 'nameMobile',
                'attr'=>['class'=>'span12 select2 customer-ledger'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->globalOption->getId())
	                    ->orderBy('e.name','ASC'),
            ])
            ->add('accountBank', EntityType::class, [
               'required'    => true,
               'class' => AccountBank::class,
               'empty_value' => '---Choose a bank---',
               'property' => 'name',
               'attr'=>['class'=>'span12 m-wrap'],
               'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                   ->where("b.globalOption =".$this->globalOption->getId())
                   ->orderBy("b.name", "ASC"),
           ])
            ->add('processHead', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => [
                    'Due' => 'Due',
                    'Advance' => 'Advance',
                    'Discount' => 'Discount',
                    'Outstanding' => 'Outstanding',
                ],
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'empty_value' => '---Choose a mobile banking---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC"),
            ])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountSales::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'accountSales';
    }

    public function getSalesInvoice()
    {
        return null;
    }


}
