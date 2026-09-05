<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Inventory\Entity\ItemBrand;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\PurchaseVendorItem;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PurchaseVendorItemType extends AbstractType
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
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'vendor base item name'],
                'constraints' =>[
                    new NotBlank(message: 'Please add vendor base item name')
                ]])

            /*->add('masterItem', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Inventory\Entity\Product',
                'choice_label' => 'name',
                'attr'=>array('class'=>'span12 select2'),
                'constraints' =>array(
                    new NotBlank(message: 'Please input required')
                ),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('p')
                        ->where("p.status = 1")
                        ->andWhere("p.inventoryConfig =".$this->inventoryConfig->getId())
                        ->orderBy("p.name","ASC");
                },
            ))*/
            ->add('brand', EntityType::class, [
                'required'    => true,
                'class' => ItemBrand::class,
                'choice_label' => 'name',
                'placeholder' => '---Choose a Brand ---',
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
            ->add('quantity',NumberType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'quantity'],
                'constraints' =>[
                    new NotBlank(message: 'Please add quantity')
                ]])

            ->add('purchasePrice',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'purchase price'],
                'constraints' =>[
                    new NotBlank(message: 'Please add purchase price')
                ]])

            ->add('subTotalPurchasePrice',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','readonly'=> true, 'placeholder'=>'sub total']])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'sales price'],
                'constraints' =>[
                    new NotBlank(message: 'Please add sales price')
                ]])

            ->add('subTotalSalesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','readonly'=> true,'placeholder'=>'sub total']])
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
