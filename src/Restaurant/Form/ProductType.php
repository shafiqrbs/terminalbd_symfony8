<?php

namespace App\Restaurant\Form;

use App\Restaurant\Entity\Category;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\Particular;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductType extends AbstractType
{
    protected $globalOption;
    private $config;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        // Derived by the Symfony 2 constructor; options replaced it and the derivation went too.
        $this->config = $options['globalOption']->getRestaurantConfig()->getId();

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter product name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter product name')
                    ]]
            )
            ->add('category', EntityType::class, [
                'required'    => false,
                'class' => Category::class,
                'choice_label' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please select required')
                ],
                'placeholder' => '---Choose a category ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                     ->where("e.status = 1")
                     ->andWhere("e.restaurantConfig = {$this->config}")
                    ->orderBy("e.sorting","ASC")
            ])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>8,'placeholder'=>'Enter product details']])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter product price'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('productionType', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'m-wrap span12'],
                'placeholder' => '---Production Type---',
                'choices' => ['Pre-production' => 'pre-production', 'Post-production' => 'post-production'],
            ])
            ->add('purchasePrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter production/purchase price']])
            ->add('file')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Particular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'product';
    }

}
