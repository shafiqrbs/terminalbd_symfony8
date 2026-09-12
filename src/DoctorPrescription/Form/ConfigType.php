<?php

namespace App\DoctorPrescription\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DoctorPrescription\Entity\DpsConfig;
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

            ->add('customerPrefix',TextType::class, ['attr'=>['class'=>'m-wrap span5','maxlength'=> 4,'placeholder'=>'max 4 char']])
            ->add('invoicePrefix',TextType::class, ['attr'=>['class'=>'m-wrap span5 ','maxlength'=> 4,'placeholder'=>'max 4 char']])
            ->add('isDefaultMedicine')
            ->add('bodyFontSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['Font Size' => '', '10px' => '10px', '12px' => '12px', '14px' => '14px', '16px' => '16px', '18px' => '18px', '20px' => '20px'],
            ])
            ->add('invoiceFontSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['Font Size' => '', '10px' => '10px', '12px' => '12px', '14px' => '14px', '16px' => '16px', '18px' => '18px', '20px' => '20px'],
            ])
            ->add('sidebarFontSize', ChoiceType::class, [
                'attr'=>['class'=>' span12'],
                'choices' => ['Font Size' => '', '10px' => '10px', '12px' => '12px', '14px' => '14px', '16px' => '16px', '18px' => '18px', '20px' => '20px'],
            ])
            ->add('customPrescription')
            ->add('invoicePrintLogo')
            ->add('isInvoiceTitle')
            ->add('isPrintHeader')
            ->add('investigation')
            ->add('isPrintFooter')
            ->add('printInstruction')
            ->add('headerLeftWidth',TextType::class,['attr'=>['class'=>'m-wrap span12']])
            ->add('headerRightWidth',TextType::class,['attr'=>['class'=>'m-wrap  span12']])
            ->add('bodyTopMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('bodyWidth',TextType::class,['attr'=>['class'=>'m-wrap  span12']])
            ->add('sidebarWidth',TextType::class,['attr'=>['class'=>'m-wrap  span12']])
            ->add('leftTopMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('invoiceHeight',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printLeftMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])
            ->add('printTopMargin',TextType::class,['attr'=>['class'=>'m-wrap numeric span12']])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DpsConfig::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_particular';
    }



}
