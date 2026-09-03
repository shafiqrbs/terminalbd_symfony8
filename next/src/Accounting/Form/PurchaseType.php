<?php

namespace App\Accounting\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Accounting\Entity\AccountVendor;
use App\Accounting\Entity\AccountHead;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\AccountPurchase;
use App\Accounting\Repository\AccountHeadRepository;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseType extends AbstractType
{
    /** @var  HospitalConfig */
    public  $option;

    public function __construct(GlobalOption $option, private readonly AccountHeadRepository $accountHead)
    {
        $this->option = $option;

    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('accountVendor', EntityType::class, [
                'required'    => true,
                'class' => AccountVendor::class,
                'empty_value' => '---Choose a vendor ---',
                'property' => 'companyName',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->option->getId()),
            ])
            ->add('accountHead', EntityType::class, [
                'required'    => true,
                'class' => AccountHead::class,
                'empty_value' => '---Choose a account head---',
                'property' => 'name',
                'attr'=>['class'=>'span12  m-wrap'],
                'constraints' =>[
                    new NotBlank(message: 'Please select account head')
                ],
                'choices'=> $this->ExpenseAccountChoiceList()
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => true,
                'class' => TransactionMethod::class,
                'property' => 'name',
                'empty_value' => '---Choose a Transaction---',
                'attr'=>['class'=>'span12 transactionMethod'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => true,
                'class' => AccountBank::class,
                'empty_value' => '---Choose a bank---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->option->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => true,
                'class' => AccountMobileBank::class,
                'empty_value' => '---Choose a mobile banking---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->option->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('file')
            ->add('totalAmount',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs','placeholder'=>'Enter total amount']])
            ->add('remark',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Enter narration']])
            ->add('payment',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Payment amount','autocomplete'=>'off']
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AccountPurchase::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchase';
    }


    /**
     * @return mixed
     */
    protected function ExpenseAccountChoiceList()
    {
        return $this->accountHead->getAccountHeadTrees();

    }

}
