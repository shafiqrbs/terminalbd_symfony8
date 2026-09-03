<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Business\Entity\BusinessParticularType;
use App\Business\Entity\Category;
use App\Business\Entity\BusinessBrand;
use App\Tool\Entity\ProductUnit;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessParticular;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class StockType extends AbstractType
{

    /** @var  $option GlobalOption  */
    public  $option;

    public function __construct(GlobalOption $option)
    {
        $this->option = $option;

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void {

	    $builder
	    ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter product name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('businessParticularType', EntityType::class, [
                'required'    => true,
                'class' => BusinessParticularType::class,
                'empty_value' => '---Choose a particular type ---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[ new NotBlank(message: 'Please select particular type') ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1"),
            ])
            ->add('salesPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter sales price']])
            ->add('purchasePrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter purchase price']])
            ->add('category', EntityType::class, [
                'required'    => false,
                'class' => Category::class,
                'property' => 'name',
                'empty_value' => '---Choose a category ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.businessConfig ={$this->option->getBusinessConfig()->getId()}")
                    ->orderBy("e.sorting","ASC")
            ])
            ->add('brand', EntityType::class, [
                'required'    => false,
                'class' => BusinessBrand::class,
                'property' => 'name',
                'empty_value' => '---Choose a brand ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.businessConfig ={$this->option->getBusinessConfig()->getId()}")
                    ->orderBy("e.name","ASC")
            ])
            ->add('unit', EntityType::class, [
                'required'    => true,
                'class' => ProductUnit::class,
                'property' => 'name',
                'empty_value' => '---Choose a unit ---',
                'attr'=>['class'=>'span12 m-wrap select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('p')
                    ->where("p.status = 1")
                    ->orderBy("p.name","ASC"),
            ])
            ->add('file');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BusinessParticular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'business_particular';
    }


}
