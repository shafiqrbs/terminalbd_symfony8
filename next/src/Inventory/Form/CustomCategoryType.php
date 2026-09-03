<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Product\Entity\Category;
use App\Inventory\Entity\InventoryConfig;
use App\Product\Entity\CategoryRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomCategoryType extends AbstractType
{

	public function __construct(private readonly InventoryConfig $inventoryConfig, private readonly CategoryRepository $em)
    {
    }


	/**
	 * @param FormBuilderInterface $builder
	 * @param array $options
	 */
	public function buildForm(FormBuilderInterface $builder, array $options): void
	{
		$builder

			->add('name',TextType::class,
                ['attr'=>[
                    'class'=>'m-wrap span12','placeholder'=>'Enter category name'],
                    'constraints' =>[
                       new NotBlank(message: 'Please input required'),
                    ]
			])
			->add('parent', EntityType::class, [
				'required'    => true,
				'empty_value' => '---Select parent category---',
				'attr'=>['class'=>'category m-wrap span12 select2'],
				'class' => \App\Product\Entity\Category::class,
				'property' => 'nestedLabel',
				'choices'=> $this->categoryChoiceList()
			])
		;
	}

	/**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
	{
		$resolver->setDefaults([
			'data_class' => Category::class
		]);
	}

	/**
	 * @return string
	 */
	public function getBlockPrefix(): string
	{
		return 'category';
	}

	/**
	 * @return mixed
	 */
	protected function categoryChoiceList()
	{

		return $categoryTree = $this->em->getFlatInventoryCategoryTree($this->inventoryConfig);

	}


}
