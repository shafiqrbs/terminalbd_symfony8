<?php

namespace App\Inventory\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Inventory\Entity\Vendor;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\PurchaseOrder;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseOrderType extends AbstractType
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
                'required'    => false,
                'class' => Vendor::class,
                'empty_value' => '---Choose a vendor ---',
                'property' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                    ->andWhere("wt.status = 1"),
            ])

            ->add('invoice',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'invoice no'],
                'constraints' =>[
                    new NotBlank(message: 'Please add  invoice no')
                ]
            ])
            ->add('memo',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'memo no']])
            /*->add('chalan','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))*/
            ->add('receiveDate',DateType::class, ['attr'=>['class'=>'m-wrap span12 datepicker','placeholder'=>''],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]])
            ->add('totalAmount',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'total amount BDT'],
                'constraints' =>[
                    new NotBlank(message: 'Please add total amount BDT')
                ]])
            ->add('paymentAmount',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'payment amount BDT'],
                'constraints' =>[
                    new NotBlank(message: 'Please add payment amount BDT')
                ]])
            ->add('dueAmount',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'due amount BDT']])
            ->add('commissionAmount',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'comission amount BDT']])
            ->add('totalQnt',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'total Qnt'],
                'constraints' =>[
                    new NotBlank(message: 'Please add total qnt')
                ]])
            ->add('totalItem',TextType::class, ['attr'=>['class'=>'m-wrap span6','placeholder'=>'total item']])
            /*->add('advanceAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('commissionAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('vatAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('taxAmount','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('totalQnt','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('totalItem','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('paymentType','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))
            ->add('paymentMethod','text', array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'')))*/
            ->add('file',FileType::class,['attr'=>['class'=>'default span12']])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PurchaseOrder::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_purchaseorder';
    }
}
