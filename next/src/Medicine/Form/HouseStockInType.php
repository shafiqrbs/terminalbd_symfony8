<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Medicine\Entity\MedicineParticular;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineStockHouse;
use App\Medicine\Entity\MedicineConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class HouseStockInType extends AbstractType
{

    /** @var  MedicineConfig  */
    public  $config;

    public function __construct(MedicineConfig $config)
    {
        $this->config = $config;

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder

            ->add('stockName',TextType::class, ['attr'=>['class'=>'m-wrap span12 select2StockMedicinePurchase input','placeholder'=>'Enter stock medicine name']
            ,'constraints' =>[ new NotBlank(message: 'Select stock name')]
            ])
            ->add('stockIn',NumberType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'quantity']])
            ->add('wearHouse', EntityType::class, [
                'required'    => true,
                'class' => MedicineParticular::class,
                'empty_value' => '---Stock House ---',
                'property' => 'name',
                'attr'=>['class'=>' span12 m-wrap'],
                'constraints' =>[ new NotBlank(message: 'Select stock house') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.particularType","pt")
                    ->where("e.status = 1")
                    ->andWhere("e.medicineConfig =".$this->config->getId())
                    ->andWhere("pt.slug = 'wear-house'"),
            ])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MedicineStockHouse::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'stockHouse';
    }
}
