<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Currency;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class InventoryConfigType extends AbstractType
{



    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('vatRegNo',TextType::class, ['attr'=>['class'=>'m-wrap span10 ','placeholder'=>'Registration no.']])
            ->add('salesReturnDayLimit',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span8']])
            ->add('shopName',TextType::class,['attr'=>['class'=>'m-wrap span12','placeholder'=>'Barcode code print shop name']])
            ->add('invoiceNote',TextType::class,['attr'=>['class'=>'m-wrap span12','placeholder'=>'POS print invoice note']])
            ->add('vatPercentage',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span10']])
            ->add('removeImage')
            ->add('autoPayment')
            ->add('androidSales')
            ->add('emptySales')
            ->add('showSalesPrice')
            ->add('file')
            ->add('usingBarcode',
                ChoiceType::class, [
                'attr'=>['class'=>'m-wrap  inline-radio'],
                'choices' => [
                    'item'  => 'Stock Item',
                    'purchase-item'   => 'Purchase-item',
                ],
                'required'    => true,
                'multiple'    => false,
                'expanded'  => true,
                'empty_data'  => "item",
            ])
            ->add('vatMode',
                ChoiceType::class, [
                'attr'=>['class'=>'m-wrap  inline-radio'],
                'choices' => [
                    'including'  => 'Including',
                    'excluding'   => 'Excluding',
                ],
                'required'    => true,
                'multiple'    => false,
                'expanded'  => true,
                'empty_data'  => "including",
            ])
            ->add('printer',
                ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'choices' => [
                    'save'          => 'Save',
                    'printer'       => 'Printer',
                    'pos'           => 'Pos Printer',
                ],
                    'required'    => true,
                    'multiple'    => false,
                    'expanded'  => false,
                    'empty_data'  => null,
            ])
            ->add('barcodePriceHide')
            ->add('isPos')
            ->add('cartImage')
            ->add('customPrint')
            ->add('vatEnable')
            ->add('systemReset')
            ->add('isAttribute')
            ->add('barcodeItem')
            ->add('isInvoice')
            ->add('isColor')
            ->add('isSize')
            ->add('isCategory')
            ->add('isModel')
            ->add('barcodeColor')
            ->add('barcodePrint')
            ->add('barcodeSize')
            ->add('isPrintFooter')
            ->add('invoicePrintLogo')
            ->add('barcodeBrandVendor',
                ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap span12'],
                    'choices' => [
                        '0' => '--Select one--',
                        '1' => 'Brand',
                        '2' => 'Vendor',
                    ],
                    'required'    => true,
                    'multiple'    => false,
                    'expanded'  => false,
                    'empty_data'  => null,
            ])
            ->add('barcodeThickness',
                ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap span12'],
                    'choices' => [
                        '20' => '20',
                        '22' => '22',
                        '24' => '24',
                        '26' => '26',
                        '28' => '28',
                        '30' => '30',
                        '32' => '32',
                        '34' => '34',
                        '36' => '36',
                        '38' => '38',
                        '40' => '40',
                    ],
                    'required'    => true,
                    'multiple'    => false,
                    'expanded'  => false,
                    'empty_data'  => 30,
            ])
            ->add('barcodeFontSize',
                ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap span12'],
                    'choices' => [
                        '7' => '7',
                        '8' => '8',
                        '9' => '9',
                        '10' => '10',
                        '11' => '11',
                        '12' => '12',
                        '13' => '13',
                    ],
                    'required'    => true,
                    'multiple'    => false,
                    'expanded'  => false,
                    'empty_data'  => 8,
            ])
            ->add('salesMode',
                ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap inline-radio'],
                    'choices' => [
                        'stock' => 'Stock',
                        'purchase-item' => 'Purchase Item'
                    ],
                    'required'    => true,
                    'multiple'    => false,
                    'expanded'  => true,
                    'empty_data'  =>"stock",
                ])
            ->add('deliveryProcess',
                ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap inline-radio'],
                    'choices' => [
                        'manual-sales' => 'Simple sales',
                        'general-sales' => 'General Sales'
                    ],
                    'empty_value' => '---Choose a sales ---',
                    'required'    => false,
                    'multiple'    => false,
                    'expanded'  => false,
                    'empty_data'  =>"manual-sales",
                ])
            ->add('barcodeScale',
                ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap span12'],
                    'choices' => [
                        '1' => '1',
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                    ],
                    'required'    => true,
                    'multiple'    => false,
                    'expanded'  => false,
                    'empty_data'  =>1,
            ])
            ->add('barcodePerRow',
                ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap span12'],
                    'choices' => [
                         1 => 1,
                        '2' => '2',
                        '3' => '3',
                        '4' => '4',
                        '5' => '5',
                        '6' => '6',
                    ],
                    'required'    => true,
                    'multiple'    => false,
                    'expanded'  => false,
                    'empty_data'  =>1,
            ])
            ->add('printLeftMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span8']])
            ->add('printTopMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span8']])
            ->add('address',TextareaType::class,['attr'=>['class'=>'m-wrap span12','placeholder'=>"Enter shop address"],'required'=>true])
            ->add('printFooterText',TextareaType::class,['attr'=>['class'=>'m-wrap span12','placeholder'=>"Enter print footer text"],'required'=>true])
            ->add('barcodeText',
                ChoiceType::class, [
                    'attr'=>['class'=>'m-wrap span12'],
                    'choices' => [
                        '' => '--Select Barcode Text--',
                        'including vat' => 'including vat',
                        'without vat' => 'without vat',
                        '+ vat' => '+ vat',
                    ],
                    'required'    => true,
                    'multiple'    => false,
                    'expanded'  => false,
                    'empty_data'  => null,
            ])
            ->add('currency', EntityType::class, [
                'required'    => true,
                'class' => Currency::class,
                'empty_value' => '---Choose a currency ---',
                'property' => 'nameWithCode',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->orderBy("e.id","ASC"),
            ])
            ->add('barcodeHeight',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span8']])
            ->add('barcodeWidth',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span8']])
            ->add('barcodeMargin',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span8']])
            ->add('barcodePadding',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span8']])
        ;
    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => InventoryConfig::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'config';
    }
}
