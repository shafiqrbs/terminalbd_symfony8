<?php

namespace App\Business\Form;


use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessConfig;
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

            ->add('address',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> 3,'placeholder'=>'Enter company address']])
            ->add('printFooterText',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> 3,'placeholder'=>'Enter print footer']])
            ->add('invoiceComment',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> 3,'placeholder'=>'Enter invoice comment']])
            ->add('bodyFontSize', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'choices' => ['Font Size' => '', '10px' => '10px', '11px' => '11px', '12px' => '12px', '13px' => '13px', '14px' => '14px', '15px' => '15px', '16px' => '16px', '17px' => '17px', '18px' => '18px', '20px' => '20px', '22px' => '22px', '24px' => '24px', '26px' => '26px', '28px' => '28px', '39px' => '30px', '32px' => '32px', '34px' => '34px', '36px' => '36px', '38px' => '38px', '40px' => '40px', '42px' => '42px', '44px' => '44px', '46px' => '46px', '48px' => '48px'],
            ])
            ->add('productionType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'choices' => ['-- Select Production --' => '', 'Pre Production' => 'pre-production', 'Post Production' => 'post-production', 'Vendor Stock' => 'vendor-stock'],
            ])
	        ->add('businessModel', ChoiceType::class, [
		        'attr'=>['class'=>'m-wrap span12'],
		        'choices' => ['-- Select business model --' => '', 'General' => 'general', 'Distribution' => 'distribution', 'Digital-Sign' => 'sign', 'Electrical' => 'electrical', 'Stationary' => 'stationary', 'Agent Commission' => 'commission', 'Event' => 'event', 'Fuel Station' => 'fuelstation', 'Bricks' => 'bricks', 'Sawmill' => 'sawmill', 'Association' => 'association'],
	        ])
	        ->add('stockFormat',
		        ChoiceType::class, [
			        'attr'=>['class'=>'m-wrap  span12'],
			        'choices' => ['Wearhouse' => 'wearhouse', 'Category' => 'category'],
			        'required'    => false,
			        'multiple'    => true,
			        'expanded'  => false,
			        'empty_data'  => null,
		        ])
            ->add('borderColor',TextType::class, ['attr'=>[
                'class'=>'m-wrap span9 colorpicker-default',
                'placeholder'=>'']
            ])
            ->add('borderWidth',TextType::class,['attr'=>['class'=>'m-wrap numeric span8','maxLength'=>2]])
            ->add('fuelStation')
            ->add('isPowered')
            ->add('isUnitPrice')
	        ->add('invoicePrintLogo')
            ->add('customInvoicePrint')
            ->add('customInvoice')
            ->add('bonusFromStock')
            ->add('isMarketingExecutive')
            ->add('isArea')
            ->add('isInvoiceTitle')
            ->add('showStock')
            ->add('isPrintHeader')
            ->add('isDescription')
            ->add('posPrint')
            ->add('isPrintFooter')
            ->add('isStockHistory')
            ->add('srCommission')
            ->add('tloCommission')
            ->add('salesReturn')
            ->add('storeLedger')
            ->add('zeroStock')
            ->add('conditionSales')
            ->add('printOutstanding')
            ->add('removeImage')
            ->add('file')
            ->add('vatPercent',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('aitPercent',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('invoiceWidth',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('invoiceHeight',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printLeftMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span8']])
            ->add('printTopMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span8']])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BusinessConfig::class
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
