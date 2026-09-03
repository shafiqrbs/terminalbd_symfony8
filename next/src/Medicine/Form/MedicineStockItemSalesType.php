<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineStock;
use App\Medicine\Entity\MedicineConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MedicineStockItemSalesType extends AbstractType
{


    public function __construct(private readonly MedicineConfig $medicineConfig)
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 stockInput autoComplete2Medicine','placeholder'=>'Enter medicine name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('openingQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span3 stockInput','placeholder'=>'Opening QTY','autoComplete'=>'off']])
            ->add('salesQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span3 stockInput','placeholder'=>'Sales QTY','autoComplete'=>'off']])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span3 stockInput','placeholder'=>'MRP','autoComplete'=>'off']])
            ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MedicineStock::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'medicineStock';
    }
}
