<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Product\Entity\Category;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\Branding;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BrandingType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')

                ]
            ])
            ->add('customUrl',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter custom url']])
            ->add('categories', EntityType::class, [
                'required'      => true,
                'multiple'      =>true,
                'class' => Category::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12 multiselect'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere("c.status = 1")
                    ->andWhere("c.level < 3")
                    ->orderBy('c.name','ASC'),
            ])


            ->add('type', ChoiceType::class, [
                'attr' =>['class'=>'selectbox'],
                'choices'   => ['National' => 'national', 'International' => 'international'],
                'multiple'  => false,
            ])

            ->add('file')
            ->add('sponsor')
            ->add('status')
        ;

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Branding::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_branding';
    }
}
