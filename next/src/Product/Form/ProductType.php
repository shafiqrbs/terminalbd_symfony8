<?php

namespace App\Product\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Branding;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Product\Entity\Product;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter item name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('vendorPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter vendor price'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter regular price'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('shipping',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter regular price'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('brand', EntityType::class, [
                'required'    => false,
                'class' => Branding::class,
                'placeholder' => '---Select brand name---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span8 selectbox'],
                'query_builder' => fn(BrandingRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere("c.status = 1")
                    ->orderBy('c.name','ASC')
            ])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter sales price']])
            ->add('quantity',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter number of quantity']])
            ->add('size',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips','data-trigger'=>'hover' , 'data-original-title'=> 'Size ie:S|M|XL/30|34|34', 'placeholder'=>'Enter size ie:S|M|XL/30|34|34']])
            ->add('weight',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips','data-trigger'=>'hover' , 'data-original-title'=> 'Product weight Kg,Pound','placeholder'=>'Enter product weight']])
            ->add('color',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips','data-trigger'=>'hover' , 'data-original-title'=> 'Color ie: Red|Green|Blue|White', 'placeholder'=>'Enter color ie: Red|Green|Blue']])
            ->add('madeIn',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips','data-trigger'=>'hover' , 'data-original-title'=> 'Product made in ie: Bangladesh,China,Japan,USA', 'placeholder'=>'Product made in ie: Bangladesh,China,Japan,USA']])
            ->add('dimension',TextType::class, ['attr'=>['class'=>'m-wrap span12  tooltips','data-trigger'=>'hover' , 'data-original-title'=> 'Product dimension ie: 200x100x50', 'placeholder'=>'Enter Product dimension ie: 200x100x50']])
            ->add('file')
            ->add('isFeature')
            ->add('content',TextareaType::class, ['attr'=>['class'=>'no-resize span12','rows'=>5]])
            ->add('status', ChoiceType::class, ['choices' => ['Publish' => '1', 'Pending' => '2', 'Expired' => '3'],
                'expanded'=>true]
            )
            ->add('availability', ChoiceType::class, [
                    'attr'=>['class'=>'selectbox span8'],
                    'choices' => ['In stock' => 'In stock', 'Pre-order' => 'Pre-order', 'Confirm sms' => 'Confirm sms', 'Out of stock' => 'Out of stock'],
                'expanded'=>false]
            )
            ->add('dealType', ChoiceType::class, [
                    'attr'=>['class'=>'selectbox span8'],
                    'choices' => ['--select deal--' => '', 'Today' => 'Today', 'Weekly' => 'Weekly', 'Monthly' => 'Monthly'],
                    'expanded'=>false]
            )
            ->add('unit', ChoiceType::class, [
                    'attr'=>['class'=>'selectbox span8'],
                    'choices' => ['Bag' => 'Bag', 'Bottle' => 'Bottle', 'Box' => 'Box', 'Can' => 'Can', 'Cft' => 'Cft', 'Coil' => 'Coil', 'Cylinder' => 'Cylinder', 'Carton' => 'Carton', 'Feet' => 'Feet', 'Gallon' => 'Gallon', 'Jar' => 'Jar', 'Job' => 'Job', 'Kg' => 'Kg', 'Liter' => 'Liter', 'Meter' => 'Meter', 'ML' => 'ML', 'MM' => 'MM', 'Nos' => 'Nos', 'Pail' => 'Pail', 'Pair' => 'Pair', 'Pcs' => 'Pcs', 'Packet' => 'Packet', 'Pound' => 'Pound', 'Prs' => 'Prs', 'Refile' => 'Refile', 'Rft' => 'Rft', 'Rim' => 'Rim', 'Roll' => 'Roll', 'Set' => 'Set', 'Sft' => 'Sft', 'Yard' => 'Yard'],
                'expanded'=>false]
            );
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'product_bundle_productbundle_product';
    }

    public function getGroupCollection()
    {

    }
}
