<?php

namespace App\Medicine\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Currency;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;


class ConfigType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('invoicePrefix',TextType::class, ['attr'=>['class'=>'m-wrap span12','maxlength'=> 4,'placeholder'=>'max 4 char']])
            ->add('instantVendorPercentage',TextType::class, ['attr'=>['class'=>'m-wrap span8 ','maxlength'=> 4,'placeholder'=>'Instant vendor item percentage']])
            ->add('tpPercent',TextType::class, ['attr'=>['class'=>'m-wrap span8 ','maxlength'=> 4,'placeholder'=>'TP Percent']])
            ->add('tpVatPercent',TextType::class, ['attr'=>['class'=>'m-wrap span8 ','maxlength'=> 4,'placeholder'=>'TP vat Percent']])
            ->add('instantVendorPercentage',TextType::class, ['attr'=>['class'=>'m-wrap span8 ','maxlength'=> 4,'placeholder'=>'Instant vendor item percentage']])

            ->add('currency', EntityType::class, [
                'required'    => false,
                'class' => Currency::class,
                'placeholder' => '--Select Currency--',
                'choice_label' => 'nameWithCode',
                'attr'=>['class'=>'m-wrap span12 inputs']

            ])
            ->add('itemSearch', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'choices' => ['Rack' => 'Rack', 'Brand' => 'Brand'],
            ])

            ->add('salesAvgHide')
            ->add('isActiveQuantity')
            ->add('isRemainingQuantity')
            ->add('invoiceActualPrice')
            ->add('printPreviousDue')
            ->add('printDiscountPercent')
            ->add('customPrint')
            ->add('purchaseItem')
            ->add('printOutstanding')
            ->add('inlineSales')
            ->add('profitLastpp')
            ->add('searchSlug')
            ->add('autoPayment')
            ->add('openingQuantity')
            ->add('isBarcode')
            ->add('posPrint')
            ->add('invoicePrintLogo')
            ->add('isPrintHeader')
            ->add('isPrintFooter')
            ->add('printOff')
            ->add('expiryDate', ChoiceType::class, [
                'attr' => [
                    'class'=>'m-wrap span12'],
                    'expanded'      =>false,
                    'placeholder' => '---Default expiry date---',
                    'multiple'      =>false,
                    'choices' => ['1 Month' => '1', '2 Month' => '2', '3 Month' => '3', '4 Month' => '4', '5 Month' => '5', '6 Month' => '6', '1 Year' => '12', '1.5 Year' => '18', '2 Years' => '24'],
            ])
            ->add('address',TextareaType::class,['attr'=>['class'=>'m-wrap span12','rows' => 4,'placeholder'=>'Enter vendor address']])
            ->add('printCss',TextareaType::class,['attr'=>['class'=>'m-wrap span12','rows' => 12,'placeholder'=>'Enter print css']])
            ->add('printFooterText',TextareaType::class,['attr'=>['class'=>'m-wrap span12','rows' => 4,'placeholder'=>'Enter print footer']])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MedicineConfig::class
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
