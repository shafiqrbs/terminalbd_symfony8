<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\ProductUnit;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Product;
use App\Inventory\Entity\InventoryConfig;
use Doctrine\ORM\EntityRepository;
use App\Product\Entity\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductType extends AbstractType
{
    protected $em;
    protected $inventoryConfig;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->em = $options['em'];
        $this->inventoryConfig = $options['inventoryConfig'];


        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add  item name'],
                'constraints' =>[
                    new NotBlank(message: 'Please add your item name')]])

            ->add('content',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> '6', 'placeholder'=>'Add  item description']])

            ->add('productUnit', EntityType::class, [
                'required'    => true,
                'class' => ProductUnit::class,
                'choice_label' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please select required')
                ],
                'placeholder' => '---Choose a item unit ---',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->orderBy("p.name","ASC"),
            ])

           ->add('category', EntityType::class, [
                'required'    => true,
                'placeholder' => '---Select parent category---',
                'attr'=>['class'=>'category m-wrap span12 select2'],
                'class' => \App\Product\Entity\Category::class,
                'choice_label' => 'nestedLabel',
                'choices'=> $this->categoryChoiceList()
            ])
            ->add('file')
            ->add('status');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'em' => null,
            'inventoryConfig' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Product::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_product';
    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {

        return $categoryTree = $this->em->getUseInventoryItemCategory($this->inventoryConfig);

    }
}
