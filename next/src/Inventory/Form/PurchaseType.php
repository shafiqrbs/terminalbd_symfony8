<?php

namespace App\Inventory\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Inventory\Entity\Vendor;
use App\Tool\Entity\TransactionMethod;
use App\Accounting\Entity\AccountBank;
use App\Accounting\Entity\AccountMobileBank;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Purchase;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseType extends AbstractType
{

    public  $inventoryConfig;

    public function __construct(InventoryConfig $inventoryConfig)
    {
        $this->inventoryConfig = $inventoryConfig;

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('vendor', EntityType::class, [
                'required'    => true,
                'class' => Vendor::class,
                'empty_value' => '---Choose a vendor ---',
                'property' => 'vendorWithCode',
                'attr'=>['class'=>'span12 select2 m-wrap purchaseInput'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->where("wt.status = 1")
                    ->andWhere("wt.inventoryConfig =".$this->inventoryConfig->getId()),
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => false,
                'class' => TransactionMethod::class,
                'property' => 'name',
                'empty_value' => '---Choose payment method---',
                'attr'=>['class'=>'span12 m-wrap transactionMethod purchaseInput'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => false,
                'class' => AccountBank::class,
                'empty_value' => '---Choose a bank---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap purchaseInput'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->inventoryConfig->getGlobalOption()->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => false,
                'class' => AccountMobileBank::class,
                'empty_value' => '---Choose a mobile banking---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap purchaseInput'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->inventoryConfig->getGlobalOption()->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('memo',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','required' => true ,'label' => 'form.name','placeholder'=>'Memo no']])

            ->add('receiveDate', DateType::class, [
                'attr' => ['class'=>'m-wrap span12 purchaseInput'],
                'view_timezone' => 'Asia/Dhaka'])

            ->add('paymentAmount',TextType::class, ['attr'=>['class'=>'purchaseInput m-wrap span12 numeric','placeholder'=>'Net payment']])
            /*
            ->add('totalQnt','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'No of Qnt'),
                'constraints' =>array(
                    new NotBlank(array('message'=>'Please add total qnt'))
                )))
            ->add('totalItem','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'No of item'),
                'constraints' =>array(
                    new NotBlank(array('message'=>'Please add total item'))
                )))
            ->add('purchaseTo', 'choice', array(
                'required'    => false,
                'attr'=>array('class'=>'m-wrap span12 purchaseInput'),
                'empty_value' => '---Choose a purchase To ---',
                'choices' => array(
                    'International' => 'International',
                    'National' => 'National',
                    'In House' => 'In House'
                ),
            ))*/
            ->add('process', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'purchaseInput m-wrap span12'],
                'choices' => [
                    'created' => 'Created',
                    'complete' => 'Complete',
                    'imported' => 'Imported',
                    'approved' => 'Approved',
                ],
            ])
          /*  ->add('asInvestment')*/
          /*  ->add('file','file',array('attr'=>array('class'=>'m-wrap span3')))*/

            /*->add('advanceAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            /*->add('advanceAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('commissionAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('vatAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('taxAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('totalQnt','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('totalItem','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('paymentType','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('paymentMethod','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))*/
            /*->add('file','file',array('attr'=>array('class'=>'default span12')))*/
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Purchase::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchase';
    }
}
