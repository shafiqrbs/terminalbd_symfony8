<?php

namespace App\Product\Form;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Product\Entity\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CollectionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')

                ]
            ])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>5]])
            ->add('discountPercentage',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter percentage float value']])
            ->add('isFeature')
            ->add('status')
            ->add('parent', EntityType::class, [
                'required'    => false,
                'class' => Collection::class,
                'placeholder' => '---Select parent collection---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span8 selectbox'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere("c.status = 1")
                    ->orderBy('c.name','ASC')
            ])

            ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collection::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'product_bundle_productbundle_collection';
    }
}
