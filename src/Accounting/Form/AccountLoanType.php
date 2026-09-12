<?php

namespace App\Accounting\Form;

use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\TransactionMethod;
use App\User\Entity\User;
use App\Accounting\Entity\AccountBank;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountLoan;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AccountLoanType extends AbstractType
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

            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span6 numeric','placeholder'=>'Amount'],
                'constraints' =>[
                    new NotBlank(message: 'Enter amount')
                ]])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add Remark']])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Select Transaction Method')
                ],
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])
            /*->add('customer', TextType::class, array('attr'=>array('class'=>'m-wrap span11 select2Customer leftMargin','placeholder'=>'Select customer name','focus' => true),
                'constraints' =>array(
                    new NotBlank(message: 'Search the customer name/mobile')
            )))*/
            ->add('employee', EntityType::class, [
                'required'    => true,
                'class' => User::class,
                'placeholder' => '---Choose a employee---',
                'choice_label' => 'userFullName',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->join('b.profile','p')
                    ->where("b.globalOption =".$this->globalOption->getId())
                    ->andWhere("p.name != 'NULL'"),
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
            ->add('transactionType', ChoiceType::class, [
		        'attr'=>['class'=>'span12 m-wrap'],
		        'expanded'      =>false,
		        'multiple'      =>false,
		        'choices' => ['Receive' => 'Debit', 'Payment' => 'Credit'],
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
            ]);
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
            'data_class' => AccountLoan::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'account';
    }

    public function getSalesInvoice()
    {
        return null;
    }

}
