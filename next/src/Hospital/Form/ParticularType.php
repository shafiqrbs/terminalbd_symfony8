<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use App\Hospital\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ParticularType extends AbstractType
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
            ->add('service', ChoiceType::class, [
                'required'    => false,
                'attr'=>['class'=>'span12'],
                'empty_value' => '---Choose a service type---',
                'choices' => [
                    'Cabin & Word'  => 'Cabin & Word',
                    'Surgery'       => 'Surgery',
                    'Pathology'     => 'Pathology',
                    'Medicine'     => 'Medicine',
                    'Operation'     => 'Operation'
                ],
            ])
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter particular name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('minimumPrice',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter particular name']])
            ->add('commission',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter particular name']])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter particular name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('service', EntityType::class, [
                'required'    => true,
                'class' => Service::class,
                'property' => 'userFullName',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy("e.id","ASC")
            ])

            ->add('department', EntityType::class, [
                'required'    => true,
                'empty_value' => '---Select category---',
                'attr'=>['class'=>'ExpenseCategory m-wrap span12 select2'],
                'class' => 'App\Hospital\Entity\Category',
                'property' => 'nestedLabel',
                'choices'=> $this->DepartmentChoiceList()
            ])

            ->add('category', EntityType::class, [
                'required'    => true,
                'empty_value' => '---Select category---',
                'attr'=>['class'=>'ExpenseCategory m-wrap span12 select2'],
                'class' => 'App\Hospital\Entity\Category',
                'property' => 'nestedLabel',
                'choices'=> $this->PathologyChoiceList()
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Particular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_particular';
    }

    /**
     * @return mixed
     */
    protected function PathologyChoiceList()
    {
        return $categoryTree = $this->emCategory->getFlatCategoryTree($this->globalOption);

    }
    /**
     * @return mixed
     */
    protected function DepartmentChoiceList()
    {
        return $categoryTree = $this->emCategory->getFlatCategoryTree($this->globalOption);

    }

}
