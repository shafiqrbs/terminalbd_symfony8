<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\HmsCategory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Repository\HmsCategoryRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryType extends AbstractType
{

    public function __construct(private readonly HmsCategoryRepository $emCategory, private readonly GlobalOption $globalOption)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter category name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('parent', EntityType::class, [
                'required'    => true,
                'empty_value' => '---Select parent category---',
                'attr'=>['class'=>'ServiceCategory m-wrap span12 select2'],
                'class' => HmsCategory::class,
                'property' => 'nestedLabel',
                'choices'=> $this->CategoryChoiceList()
            ])
            ->add('status')


        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HmsCategory::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'product_bundle_hospitalbundle_category';
    }

    /**
     * @return mixed
     */
    protected function CategoryChoiceList()
    {
        return $this->emCategory->getFlatCategoryTree($this->globalOption);

    }


}
