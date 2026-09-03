<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PurchaseItemAttributeType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('serialNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Attribute name']])
            ->add('expiredDate', DateType::class, [
                'widget' => 'single_text',
                'required' => 'false',
                'placeholder' => [
                    'mm' => 'mm', 'dd' => 'dd','YY' => 'YY'

                ],
                'format' => 'dd-MM-yyyy',
                'attr' => ['class'=>'m-wrap span12 dateCalendar'],
                'view_timezone' => 'Asia/Dhaka'])

            ->add('assuranceFromVendor', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => [
                    '1 Month' => '1 Month',
                    '3 Month' => '3 Month',
                    '4 Month' => '4 Month',
                    '6 Month' => '6 Month',
                    '1 Year' => '1 Year',
                    '1 Year 6 Month' => '1 Year 6 Month',
                    '2 Year' => '2 Year',
                    '2 Year 6 Month' => '2 Year 6 Month',
                    '3 Year' => '3 Year',
                    '3 Year 6 Month' => '3 Year 6 Month',
                    '4 Year' => '4 Year',
                    '4 Year 6 Month' => '4 Year 6 Month',
                    '5 Year' => '5 Year',
                    '5 Year 6 Month' => '5 Year 6 Month',
                    'Life Time' => 'Life Time',
                    'Others' => 'Others',

                ],
                'data' => $options->getData() ?: '1 Year'
            ])
            ->add('assuranceToCustomer', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => [
                    '1 Month' => '1 Month',
                    '3 Month' => '3 Month',
                    '4 Month' => '4 Month',
                    '6 Month' => '6 Month',
                    '1 Year' => '1 Year',
                    '1 Year 6 Month' => '1 Year 6 Month',
                    '2 Year' => '2 Year',
                    '2 Year 6 Month' => '2 Year 6 Month',
                    '3 Year' => '3 Year',
                    '3 Year 6 Month' => '3 Year 6 Month',
                    '4 Year' => '4 Year',
                    '4 Year 6 Month' => '4 Year 6 Month',
                    '5 Year' => '5 Year',
                    '5 Year 6 Month' => '5 Year 6 Month',
                    'Life Time' => 'Life Time',
                    'Others' => 'Others',
                ],
                'data' => $options->getData() ? : '1 Year'
            ])
            ->add('assuranceType', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => [
                    'Warranty' => 'Warranty',
                    'Grantee' => 'Grantee',
                    'Others' => 'Others',
                ],
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'App\Inventory\Entity\PurchaseItemAttribute'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_purchaseItemAttribute';
    }


}
