<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\User\Entity\User;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use App\Accounting\Entity\ExpenseCategory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\Expenditure;
use App\Accounting\Repository\ExpenseCategoryRepository;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ExpenditureType extends AbstractType
{

    /** @var  GlobalOption */

    public  $globalOption;

    public function __construct(GlobalOption $globalOption, private readonly ExpenseCategoryRepository $expenseCategoryRepository )
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

            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Amount'],
                'constraints' =>[
                    new NotBlank(message: 'Please add payment amount BDT')
                ]])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Remark...']])
            ->add('toUser', EntityType::class, [
                'required'    => true,
                'class' => User::class,
                'empty_value' => '---Choose a account user---',
                'property' => 'userFullName',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[new NotBlank(message: 'Please select to user name')],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')
                    ->where("u.isDelete != 1")
                    ->andWhere("u.domainOwner = 2")
                    ->andWhere("u.globalOption =".$this->globalOption->getId())
                    ->orderBy("u.username", "ASC"),
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'empty_value' => '---Choose a transaction method---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap transactionMethod'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.slug != 'cash-on-delivery'")
                    ->orderBy("e.id")
            ])
            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'empty_value' => '---Choose a bank---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->globalOption->getId()),
            ])

            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'empty_value' => '---Choose a mobile account---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->globalOption->getId()),
            ])

            ->add('expenseCategory', EntityType::class, [
                'required'    => true,
                'constraints' =>[
                    new NotBlank(message: 'Please select category')
                ],
                'empty_value' => '---Select expense category---',
                'attr'=>['class'=>'ExpenseCategory m-wrap span12 select2'],
                'class' => ExpenseCategory::class,
                'property' => 'nestedLabel',
                'choices'=> $this->ExpenseCategoryChoiceList()
            ])

            /*->add('toUser', 'entity', array(
                'required'    => true,
                'class' => 'App\User\Entity\User',
                'empty_value' => '---Choose a user---',
                'property' => 'userFullName',
                'attr'=>array('class'=>'span12 m-wrap'),
                'constraints' =>array(new NotBlank(array('message'=>'Please select to user name'))),
                'query_builder' => function(EntityRepository $er){
	                return $er->createQueryBuilder('u')
	                          ->where("u.isDelete != 1")
	                          ->andWhere("u.enabled = 1")
	                          ->andWhere("u.domainOwner = 2")
	                          ->andWhere("u.globalOption =".$this->globalOption->getId())
	                          ->orderBy("u.username", "ASC");
                },
            ))*/
            ->add('file');
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Expenditure::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'expenditure';
    }

    /**
     * @return mixed
     */
    protected function ExpenseCategoryChoiceList()
    {
        return $ExpenseCategoryTree = $this->expenseCategoryRepository->getFlatExpenseCategoryTree($this->globalOption);

    }



}
