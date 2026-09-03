<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\ItemSize;
use App\Inventory\Entity\InventoryConfig;
use App\Product\Entity\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ItemSizeType extends AbstractType
{

    public function __construct(private readonly CategoryRepository $em, private readonly InventoryConfig $inventoryConfig)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add  size/dimension/weight/others name'],
                'constraints' =>[
                    new NotBlank(message: 'Please add  size/dimension/weight/others name')
            ]])

            ->add('category', EntityType::class, [
                'expanded'      =>true,
                'multiple'      =>true,
                'required'    => true,
                'empty_value' => '---Select parent category---',
                'attr'=>['class'=>'category m-wrap span12 check-list'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'class' => \App\Product\Entity\Category::class,
                'property' => 'nestedLabel',
                'choices'=> $this->categoryChoiceList()
            ])
            ->add('status');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ItemSize::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_ItemSize';
    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {

        return $categoryTree = $this->em->getUseInventoryItemCategory($this->inventoryConfig);

    }
}
