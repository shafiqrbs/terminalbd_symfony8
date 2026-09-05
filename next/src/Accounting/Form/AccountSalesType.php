<?php

namespace App\Accounting\Form;

use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountSales;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountSalesType extends AbstractType
{

    public  $globalOption;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span4 numeric removeZero','placeholder'=>'Received amount'],
                'constraints' =>[
                    new NotBlank(message: 'Add payment amount')
                ]])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add remark']])
            ->add('customer',TextType::class, ['attr'=>['class'=>'m-wrap span9 select2Customer leftMargin','placeholder'=>'Select customer name','focus' => true]])
            ->add('smsAlert',CheckboxType::class, ['attr'=>['class'=>'']])
            ->add('processHead', ChoiceType::class, [
		        'attr'=>['class'=>'span12 m-wrap'],
		        'expanded'      =>false,
		        'multiple'      =>false,
		        'choices' => ['Receive from Due' => 'Due', 'Discount' => 'Discount', 'Receive of Advance' => 'Advance', 'Debit Adjustment(Receive)' => 'Debit', 'Credit Adjustment(Payment)' => 'Credit'],
	        ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'placeholder' => '---Choose a Transaction Method---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])
            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.globalOption =".$this->globalOption->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile banking---',
                'choice_label' => 'name',
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
            'globalOption' => null,
        ]);

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
