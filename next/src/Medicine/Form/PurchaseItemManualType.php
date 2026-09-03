<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicinePurchaseItem;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class PurchaseItemManualType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('stockName',TextType::class, ['attr'=>['class'=>'m-wrap span12 select2StockMedicinePurchase input','placeholder'=>'Enter stock medicine name']])
            ->add('expirationEndDate',TextType::class, ['attr'=>['class'=>'m-wrap span12 dateCalendar input','placeholder'=>'Expiry date','autoComplete'=>'off']])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap purchase-input span12 input','placeholder'=>'Total MRP','autoComplete'=>'off']])
            ->add('purchasePrice',TextType::class, ['attr'=>['class'=>'m-wrap purchase-input span12 input','placeholder'=>'Total Purchase','autoComplete'=>'off']])
            ->add('bonusQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span9','placeholder'=>'Bonus QTY','autoComplete'=>'off']])
            ->add('itemPercent',NumberType::class, ['attr'=>['class'=>'m-wrap span8 numeric','placeholder'=>'Item Percent','autoComplete'=>'off']])
            ->add('quantity',NumberType::class, ['attr'=>['class'=>'m-wrap purchase-input span8 form-control input-number input red-border','placeholder'=>'Quantity','autoComplete'=>'off']]);
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
    public function discountPercentList()
    {
        $array = [

            1=>1,
            2=>2,
            3=>3,
            4=>4,
            5=>5,
            6=>6,
            7=>7,
            8=>8,
            9=>9,
            10=>10,
            11=>11,
            12=>12,
            13=>13,
            14=>14,
            15=>15,
            16=>16,
            17=>17,
            18=>18,
            19=>19,
            20=>20,
            21=>21,
            22=>22,
            23=>23,
            24=>24,
            25=>25,
            26=>26,
            27=>27,
            28=>28,
            29=>29,
            30=>30,
            31=>31,
            32=>32,
            33=>33,
            34=>34,
            35=>35,
            36=>36,
            37=>37,
            38=>38,
            39=>39,
            40=>40,
            41=>41,
            42=>42,
            43=>43,
            44=>44,
            45=>45,
            46=>46,
            47=>47,
            48=>48,
            49=>49,
            50=>50
        ];
        return $array;


    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'purchaseItem';
    }
}
