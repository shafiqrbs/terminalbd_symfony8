<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\ProductUnit;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use App\Product\Entity\CategoryRepository;
use App\Product\Entity\ItemGroupRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditItemType extends AbstractType
{

    /** @var  ItemGroupRepository */
    private $groupRep;

    /** @var  CategoryRepository */
    private $catRep;

    /** @var InventoryConfig */
    public  $inventoryConfig;

    public function __construct(InventoryConfig $inventoryConfig, ItemGroupRepository $groupRep, CategoryRepository $catRep )
    {
        $this->inventoryConfig = $inventoryConfig;
        $this->category = $catRep;
        $this->itemGroup = $groupRep;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder

            ->add('name', TextType::class, [
                'required'    => true,
                'attr'=>['class'=>'span12'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('itemUnit', EntityType::class, [
                'required'    => false,
                'class' => ProductUnit::class,
                'property' => 'name',
                'empty_value' => '---Choose a item unit ---',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->orderBy("p.name","ASC"),
            ])
            ->add('salesPrice', TextType::class, [
                'required'    => false,
                'attr'=>['class'=>'span12']
            ])
            ->add('purchasePrice', TextType::class, [
                'required'    => false,
                'attr'=>['class'=>'span12']
            ])
            ->add('file');

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Item::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'item';
    }

}
