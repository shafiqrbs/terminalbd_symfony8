<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\ProductUnit;
use App\Inventory\Entity\ItemColor;
use App\Inventory\Entity\ItemSize;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Item;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use App\Product\Entity\CategoryRepository;
use App\Product\Entity\ItemGroupRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ItemType extends AbstractType
{
    protected $itemGroup;
    protected $category;


    /** @var  ItemGroupRepository */
    private $groupRep;

    /** @var  CategoryRepository */
    private $catRep;

    /** @var InventoryConfig */
    public  $inventoryConfig;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->inventoryConfig = $options['inventoryConfig'];
        $this->itemGroup = $options['groupRep'];
        $this->category = $options['catRep'];


        $builder
            ->add('category', TextType::class, [
                'required' => true,
                'mapped' => false,
                'attr' => ['class' => 'span12', 'list' => 'categories', 'autocomplete' => 'off'],
                'constraints' => [
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('inventoryConfig', HiddenType::class,
                [
                    'mapped' => false,
                    'attr' => ['data' => $this->inventoryConfig->getId()]])
            /*->add('itemGroup', EntityType::class, array(
                'required'    => false,
                'placeholder' => '---Select item group---',
                'attr'=>array('class'=>'category m-wrap span12 select2'),
                'class' => \App\Product\Entity\ItemGroup::class,
                'choice_label' => 'nestedLabel',
                'choices'=> $this->ItemGroupChoiceList()
            ))*/

            ->add('name', TextType::class, [
                'required' => true,
                'attr' => ['class' => 'span12', 'list' => 'masterItem', 'autocomplete' => 'off'],
                'constraints' => [
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('nameBn', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'span12', 'list' => 'masterItem', 'autocomplete' => 'off']
            ])
            ->add('itemUnit', EntityType::class, [
                'required' => false,
                'class' => ProductUnit::class,
                'choice_label' => 'name',
                'data' => 4,
                'placeholder' => '---Choose a item unit ---',
                'attr' => ['class' => 'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->orderBy("p.name", "ASC"),
            ])
            ->add('salesPrice', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'span12']
            ])
            ->add('purchasePrice', TextType::class, [
                'required' => false,
                'attr' => ['class' => 'span12']
            ])
           /* ->add('file')*/

            ->add('barcode', TextType::class, [
            'required'    => false,
            'attr'=>['class'=>'span12 barcode']
        ]);

            if($this->inventoryConfig->getIsVendor() == 1 ){

                $builder
                    ->add('vendor', TextType::class, [
                        'required'    => true,
                        'mapped'    => false,
                        'attr'=>['class'=>'span12','list'=>'vendors','autocomplete'=>'off'],
                        'constraints' =>[
                            new NotBlank(message: 'Please input required')
                        ]
                    ]);

            }
            if($this->inventoryConfig->isModel() == 1 ){

                $builder
                    ->add('model', TextType::class, [
                        'required'    => true,
                        'attr'=>['class'=>'span12 model'],
                        'constraints' =>[
                            new NotBlank(message: 'Please input required')
                        ]
                    ]);

            }
            if($this->inventoryConfig->getIsColor() == 1 ) {

                $builder
                ->add('color', EntityType::class, [
                    'required' => true,
                    'class' => ItemColor::class,
                    'placeholder' => '---Choose a color ---',
                    'choice_label' => 'name',
                    'attr' => ['class' => 'span12 select2'],
                    'constraints' => [
                        new NotBlank(message: 'Please input required')
                    ],
                    'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('v')
                        ->where("v.status = 1"),
                ]);

            }
            if($this->inventoryConfig->getIsSize() == 1 ) {

                $builder
                    ->add('size', EntityType::class, [
                        'required'    => true,
                        'class' => ItemSize::class,
                        'placeholder' => '---Choose a size/weight ---',
                        'choice_label' => 'name',
                        'attr'=>['class'=>'span12 select2'],
                        'constraints' =>[
                            new NotBlank(message: 'Please input required')
                        ],
                        'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('v')
                            ->where("v.status = 1"),
                    ]);

            }

            if($this->inventoryConfig->getIsBrand() == 1 ) {

                $builder
                    ->add('brand', TextType::class, [
                        'required'    => true,
                        'mapped'    => false,
                        'attr'=>['class'=>'span12','list'=>'brands','autocomplete'=>'off'],
                        'constraints' =>[
                            new NotBlank(message: 'Please input required')
                        ]
                    ]);

            }

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'inventoryConfig' => null,
            'groupRep' => null,
            'catRep' => null,
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
        return 'item';
    }

    /**
     * @return mixed
     */
    protected function ItemGroupChoiceList()
    {
        return $itemTypeTree = $this->itemGroup->getFlatInventoryItemGroupTree($this->inventoryConfig);

    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {

        return $categoryTree = $this->category->getFlatInventoryCategoryTree($this->inventoryConfig);

    }
}
