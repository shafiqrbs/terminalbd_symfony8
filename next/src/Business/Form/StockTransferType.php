<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Business\Entity\WearHouse;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\ProductTransfer;
use App\Business\Entity\BusinessConfig;
use App\Hospital\Entity\HospitalConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class StockTransferType extends AbstractType
{

    /** @var  HospitalConfig */
    public  $config;

    public function __construct(BusinessConfig $config)
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
            ->add('businessParticular',
                TextType::class, ['attr'=>['class'=>'m-wrap span12 select2ParticularName input','placeholder'=>'Enter stock name'],
                'mapped' => false,
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('fromWearHouse', EntityType::class, [
                'required'    => true,
                'class' => WearHouse::class,
                'empty_value' => '---Choose a from wearhouse ---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.businessConfig =".$this->config->getId()),
            ])
            ->add('toWearHouse', EntityType::class, [
                'required'    => true,
                'class' => WearHouse::class,
                'empty_value' => '---Choose a to wearhouse ---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[ new NotBlank(message: 'Please select your vendor name') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.businessConfig =".$this->config->getId()),
            ])
            ->add('quantity',NumberType::class, ['attr'=>['class'=>'m-wrap span12 form-control input-number input','placeholder'=>'Quantity']])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter notes ']])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductTransfer::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'transfer';
    }
}
