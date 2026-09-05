<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Inventory\Entity\Product;
use App\Inventory\Entity\ItemBrand;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Location\Entity\Country;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\PurchaseVendorItem;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseVendorItemDetailsType extends AbstractType
{

    /** @var InventoryConfig */

    public  $inventoryConfig;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->inventoryConfig = $options['inventoryConfig'];

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','readonly'=>'readonly', 'placeholder'=>'vendor base item name'],
                'constraints' =>[
                    new NotBlank(message: 'Please add vendor base item name')
                ]])
            ->add('webName',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'web base item name']])
            ->add('webPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'web price']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'no-resize span12','rows'=>5]])
            ->add('masterItem', EntityType::class, [
                'required'    => true,
                'class' => Product::class,
                'placeholder' => '---Choose a item ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2', 'readonly'=>'readonly'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->andWhere("p.inventoryConfig =".$this->inventoryConfig->getId())
                    ->orderBy("p.name","ASC"),
            ])
            ->add('brand', EntityType::class, [
                'required'    => true,
                'class' => ItemBrand::class,
                'placeholder' => '---Choose a brand ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->andWhere("p.inventoryConfig =".$this->inventoryConfig->getId())
                    ->orderBy("p.name","ASC"),
            ])
            ->add('warningText',TextType::class, ['attr'=>['class'=>'m-wrap span12']])
            ->add('warningLabel', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'span12'],
                'placeholder' => '---Choose a warning label---',
                'choices' => ['Warranty' => 'Warranty', 'Guarantee' => 'Guarantee'],
            ])
            ->add('country', EntityType::class, [
                'required'    => true,
                'class' => Country::class,
                'placeholder' => '---Choose a country ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->orderBy("p.name","ASC"),
            ])

            ->add('ageGroup', ChoiceType::class, [
                'attr'=>['class'=>'span6 select2'],
                'choices' => ['Kids' => 'Kids', 'Adult' => 'Adult'],
            ])

            ->add('gender', ChoiceType::class, [
                'attr'=>['class'=>'span12 select2'],
                'choices' => ['Male' => 'Male', 'Female' => 'Female'],
            ])
            ->add('file')
            ->add('isWeb')

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'inventoryConfig' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => PurchaseVendorItem::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_purchasevendoritem';
    }
}
