<?php

namespace App\Restaurant\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Restaurant\Entity\Particular;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\ProductionBatch;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductionBatchType extends AbstractType
{



    public function __construct(private readonly GlobalOption $globalOption)
    {

        $this->config               = $this->globalOption->getRestaurantConfig()->getId();
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('productionItem', EntityType::class, [
                'required'    => false,
                'class' => Particular::class,
                'property' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please select required')
                ],
                'empty_value' => '---Choose a production item ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join('e.service','s')
                     ->where("e.status = 1")
                     ->andWhere("e.restaurantConfig = {$this->config}")
                     ->andWhere("e.productionType = 'pre-production'")
                     ->orderBy("e.sorting","ASC")
            ])
            ->add('quantity',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter initiate quantity','constraints' =>[
        new NotBlank(message: 'Please select required')
    ]]])
            ->add('issueQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter issue quantity','constraints' =>[
                new NotBlank(message: 'Please select required')
            ]]])
            ->add('damageQuantity',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter damage quantity']])

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductionBatch::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'productionBatch';
    }


}
