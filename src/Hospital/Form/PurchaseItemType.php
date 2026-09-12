<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicinePurchaseItem;
use App\Hospital\Entity\HospitalConfig;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PurchaseItemType extends AbstractType
{
    /** @var  HospitalConfig */
    public  $option;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->option = $options['option'];

        $builder
            ->add('stockName',TextType::class, ['attr'=>['class'=>'m-wrap span12 select2StockMedicinePurchase input','placeholder'=>'Enter stock medicine name']])
            ->add('expirationEndDate',TextType::class, ['attr'=>['class'=>'m-wrap span3 dateCalendar input','placeholder'=>'Expiry date','autoComplete'=>'off']])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap purchase-input span2 input','placeholder'=>'MRP','autoComplete'=>'off']])
            ->add('bonusQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span2','placeholder'=>'Bonus QTY','autoComplete'=>'off']])
            ->add('quantity',NumberType::class, ['attr'=>['class'=>'m-wrap purchase-input span2 form-control input-number input red-border','placeholder'=>'Quantity','autoComplete'=>'off']]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'option' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => MedicinePurchaseItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchaseItem';
    }
}
