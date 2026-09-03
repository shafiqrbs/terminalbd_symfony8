<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicinePurchaseItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PurchaseBarcodeItemType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['mapped'=>false,'attr'=>['class'=>'m-wrap span12  barcodeInput','placeholder'=>'Enter stock medicine name']])
            ->add('barcode',TextType::class, ['attr'=>['class'=>'m-wrap span12 barcode barcodeInput','placeholder'=>'Enter Item Barcode','autoComplete'=>'off']])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap purchase-input span6 barcodeInput','placeholder'=>'Total MRP','autoComplete'=>'off']])
            ->add('bonusQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span9','placeholder'=>'Bonus QTY','autoComplete'=>'off']])
            ->add('quantity',NumberType::class, ['attr'=>['class'=>'m-wrap barcodeInput span12 input-number red-border','placeholder'=>'Quantity','autoComplete'=>'off']]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MedicinePurchaseItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'barcodePurchaseItem';
    }
}
