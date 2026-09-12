<?php

namespace App\Restaurant\Form;

use App\Restaurant\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Restaurant\Entity\Service;
use App\Restaurant\Entity\Particular;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CategoryType extends AbstractType
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
            ->add('service', EntityType::class, [
                'required'    => false,
                'class' => Service::class,
                'choice_label' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Please select required')
                ],
                'placeholder' => '---Choose product type ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere('e.slug IN (:slugs)')
                    ->setParameter('slugs',['consuamble','stockable','product'])
                    ->orderBy("e.sorting","ASC")
            ])

            ->add('productGroup', EntityType::class, [
                'required'    => false,
                'class' => Particular::class,
                'choice_label' => 'name',
                'placeholder' => '---Choose product group ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.service",'s')
                    ->where("e.status = 1")
                    ->andWhere('s.slug IN (:slugs)')
                    ->setParameter('slugs',['product-group'])
                    ->andWhere("e.restaurantConfig = {$this->config}")
                    ->orderBy("e.sorting","ASC")
            ])

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter category name'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter category name')
                ]]
            );
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

}
