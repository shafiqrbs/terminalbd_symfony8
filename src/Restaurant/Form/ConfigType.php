<?php

namespace App\Restaurant\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Currency;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\RestaurantConfig;
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

            ->add('vatRegNo',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter vat registration no']])
            ->add('vatPercentage',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span5','max'=> 100]])
            ->add('servicePercentage',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span5','max'=> 100]])
            ->add('address',TextareaType::class,['attr'=>['class'=>'m-wrap span11','rows'=>5,'placeholder'=> "Enter office/store address"]])
            ->add('invoiceNote',TextareaType::class,['attr'=>['class'=>'m-wrap span11','rows' => 3,'placeholder'=> "Enter invoice notes"]])
            ->add('printFooterText',TextareaType::class,['attr'=>['class'=>'m-wrap span11','rows' => 3,'placeholder'=> "Enter print footer text"]])
            ->add('currency', EntityType::class, [
                'required'    => false,
                'class' => Currency::class,
                'placeholder' => '--Select Currency--',
                'choice_label' => 'nameWithCode',
                'attr'=>['class'=>'m-wrap span12 inputs select2']

            ])
            ->add('autoPayment')
            ->add('isCustomer')
            ->add('vatEnable')
            ->add('tablePlan')
            ->add('productImage')
            ->add('additionalTable')
            ->add('additionalServe')
            ->add('posPrint')
            ->add('deliveryPrint')
            ->add('invoicePrintLogo')
            ->add('isStockHistory')
            ->add('isInvoiceTitle')
            ->add('isPrintHeader')
            ->add('isPrintFooter')
            ->add('isPrintFooter')
            ->add('printInstruction')
            ->add('kitchenPrint')
            ->add('printToken')
            ->add('isProduction')
            ->add('vatMode', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'m-wrap span12'],
                'placeholder' => '---VAT Mode---',
                'choices' => ['Including' => 'including', 'Excluding' => 'excluding'],
            ])
            ->add('discountType', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'m-wrap span12'],
                'placeholder' => '---Discount Type---',
                'choices' => ['Percentage' => 'percentage', 'Flat' => 'flat'],
            ])
             ->add('salesMode', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'m-wrap span12'],
                'placeholder' => '---Sales Mode---',
                'choices' => ['Category' => 'category', 'Table' => 'table'],
            ])
            ->add('payFor', ChoiceType::class, [
                'required'    => true,
                'attr'=>['class'=>'m-wrap span12'],
                'choices' => ['Pre-pay' => 'pre-pay', 'Post-pay' => 'post-pay'],
            ])
            ->add('discountPercentage',IntegerType::class,['attr'=>['class'=>'m-wrap numeric span5']])
            ->add('invoiceHeight',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printLeftMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printTopMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('templateCss',TextareaType::class,['attr'=>['class'=>'m-wrap span12','rows'=>10]])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RestaurantConfig::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'restaurant_config';
    }


}
