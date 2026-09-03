<?php

namespace App\Restaurant\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Restaurant\Entity\Service;
use App\Restaurant\Entity\Category;
use App\Tool\Entity\ProductUnit;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\Particular;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class StockType extends AbstractType
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

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter product name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('productionType', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'m-wrap span12'],
                'empty_value' => '---Production Type---',
                'choices' => [
                    'pre-production' => 'Pre-production',
                    'post-production' => 'Post-production'
                ],
            ])
            ->add('service', EntityType::class, [
                'required'    => false,
                'class' => Service::class,
                'property' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please select required')
                ],
                'empty_value' => '---Choose product type ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere('e.slug IN (:slugs)')
                    ->setParameter('slugs',['consuamble','stockable','product'])
                    ->orderBy("e.sorting","ASC")
            ])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter price']])
            ->add('purchasePrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter price']])
            ->add('category', EntityType::class, [
                'required'    => false,
                'class' => Category::class,
                'group_by'  => 'service.name',
                'property' => 'name',
                'choice_translation_domain' => true,
                'empty_value' => '---Choose a category ---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.restaurantConfig = {$this->config}")
                    ->orderBy("e.sorting","ASC")
            ])
            ->add('unit', EntityType::class, [
                'required'    => true,
                'class' => ProductUnit::class,
                'property' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please select required')
                ],
                'empty_value' => '---Choose a unit ---',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->orderBy("p.name","ASC"),
            ])
            ->add('file');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Particular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'particular';
    }


}
