<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineSalesItem;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SalesItemType extends AbstractType
{
    protected $option;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->option = $options['option'];

        $builder

            ->add('stockName',TextType::class, ['attr'=>['class'=>'m-wrap span12 select2StockMedicine input','placeholder'=>'Enter stock medicine name']])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12 input number','placeholder'=>'MRP']])
            ->add('quantity',TextType::class, ['attr'=>['class'=>'m-wrap span5 form-control input-number number input','placeholder'=>'QTY']])
            ->add('itemPercent',TextType::class, ['attr'=>['class'=>'m-wrap span8 form-control input-number number input','placeholder'=>'Disc(%)']]);
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
            'data_class' => MedicineSalesItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'salesitem';
    }
}
