<?php

namespace App\Inventory\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Inventory\Entity\Product;
use App\Inventory\Entity\Vendor;
use App\Inventory\Entity\ItemColor;
use App\Inventory\Entity\ItemSize;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\InventoryConfig;
use App\Inventory\Repository\ItemTypeGroupingRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ItemSearchType extends AbstractType
{

    /** @var  ItemTypeGroupingRepository */

    private $em;

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
            ->add('masterItem', EntityType::class, [
                'required'    => true,
                'class' => Product::class,
                'placeholder' => '---Choose a item ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span3 select2Product'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->andWhere("p.inventoryConfig =".$this->inventoryConfig->getId())
                    ->orderBy("p.name","ASC"),
            ])
            ->add('vendor', EntityType::class, [
                'required'    => true,
                'class' => Vendor::class,
                'placeholder' => '---Choose a vendor ---',
                'choice_label' => 'companyName',
                'attr'=>['class'=>'span3 select2Vendor'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('v')
                    ->where("v.status = 1")
                    ->andWhere("v.inventoryConfig =".$this->inventoryConfig->getId()),
            ])
            ->add('color', EntityType::class, [
                'required'    => true,
                'class' => ItemColor::class,
                'placeholder' => '---Choose a color ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span3 select2Color'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('v')
                    ->where("v.status = 1")
                    ->andWhere("v.inventoryConfig =".$this->inventoryConfig->getId()),
            ])
            ->add('size', EntityType::class, [
                'required'    => true,
                'class' => ItemSize::class,
                'placeholder' => '---Choose a size ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span3 select2Size'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('v')
                    ->where("v.status = 1")
                    ->andWhere("v.inventoryConfig =".$this->inventoryConfig->getId()),
            ])
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
            'data_class' => Item::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_item';
    }

    /**
     * @return mixed
     */
    protected function ItemTypeChoiceList()
    {
        return $itemTypeTree = $this->em->getItemTypeTree();

    }
}
