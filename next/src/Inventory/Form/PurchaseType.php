<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\FileType;
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

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->inventoryConfig = $options['inventoryConfig'];

        $builder

            ->add('vendor', EntityType::class, [
                'required'    => true,
                'class' => Vendor::class,
                'placeholder' => '---Choose a vendor ---',
                'choice_label' => 'vendorWithCode',
                'attr'=>['class'=>'span12 select2 m-wrap purchaseInput'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->where("wt.status = 1")
                    ->andWhere("wt.inventoryConfig =".$this->inventoryConfig->getId()),
            ])
            ->add('transactionMethod', EntityType::class, [
                'required'    => false,
                'class' => TransactionMethod::class,
                'choice_label' => 'name',
                'placeholder' => '---Choose payment method---',
                'attr'=>['class'=>'span12 m-wrap transactionMethod purchaseInput'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])

            ->add('accountBank', EntityType::class, [
                'required'    => false,
                'class' => AccountBank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap purchaseInput'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.globalOption =".$this->inventoryConfig->getGlobalOption()->getId())
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('accountMobileBank', EntityType::class, [
                'required'    => false,
                'class' => AccountMobileBank::class,
                'placeholder' => '---Choose a mobile banking---',
                'choice_label' => 'name',
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
            ->add('totalQnt', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'No of Qnt'),
                'constraints' =>array(
                    new NotBlank(array('message'=>'Please add total qnt'))
                )))
            ->add('totalItem', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'No of item'),
                'constraints' =>array(
                    new NotBlank(array('message'=>'Please add total item'))
                )))
            ->add('purchaseTo', ChoiceType::class, array(
                'required'    => false,
                'attr'=>array('class'=>'m-wrap span12 purchaseInput'),
                'placeholder' => '---Choose a purchase To ---',
                'choices' => array('International' => 'International', 'National' => 'National', 'In House' => 'In House'),
            ))*/
            ->add('process', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'purchaseInput m-wrap span12'],
                'choices' => ['Created' => 'created', 'Complete' => 'complete', 'Imported' => 'imported', 'Approved' => 'approved'],
            ])
          /*  ->add('asInvestment')*/
          /*  ->add('file', FileType::class,array('attr'=>array('class'=>'m-wrap span3')))*/

            /*->add('advanceAmount', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            /*->add('advanceAmount', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('commissionAmount', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('vatAmount', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('taxAmount', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('totalQnt', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('totalItem', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('paymentType', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('paymentMethod', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))*/
            /*->add('file', FileType::class,array('attr'=>array('class'=>'default span12')))*/
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'inventoryConfig' => null,
        ]);

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
