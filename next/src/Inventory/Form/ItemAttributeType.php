<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\ItemAttribute;
use App\Product\Entity\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ItemAttributeType extends AbstractType
{

    public function __construct(private readonly CategoryRepository $em)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Attribute name'],
                'constraints' =>[
                    new NotBlank(message: 'Please add  attribute name')
            ]])
            ->add('category', EntityType::class, [
                'required'    => true,
                'empty_value' => '---Select parent category---',
                'attr'=>['class'=>'category m-wrap span12 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'class' => \App\Product\Entity\Category::class,
                'property' => 'nestedLabel',
                'choices'=> $this->categoryChoiceList()
            ])
            ->add('placeholder',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Attribute placeholder']])
            ->add('toolTip',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Attribute tooltip']])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ItemAttribute::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_ItemAttribute';
    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {

        return $categoryTree = $this->em->getFlatCategoryTree();

    }
}
