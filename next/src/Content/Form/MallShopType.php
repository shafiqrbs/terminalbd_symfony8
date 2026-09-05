<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Product\Entity\Category;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MallShopType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('shopNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter shop name optional only non registered customer'],
                 'constraints' =>[
                        new NotBlank(message: 'Please input required')
                ]
            ])

            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Enter name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('contact',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('level', ChoiceType::class, [
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'attr'=>['class'=>'select2 span12'],
                'choices' => ['Select block' => '', 'Level-01' => 'Level-01', 'Level-02' => 'Level-02', 'Level-03' => 'Level-03', 'Level-04' => 'Level-04', 'Level-05' => 'Level-05', 'Level-06' => 'Level-06', 'Level-07' => 'Level-07', 'Level-08' => 'Level-08', 'Level-09' => 'Level-09', 'Level-10' => 'Level-10'],
            ])
            ->add('block', ChoiceType::class, [
                'attr'=>['class'=>'select2 span12'],
                'choices' => ['---Select block----' => '', 'Block-A' => 'Block-A', 'Block-B' => 'Block-B', 'Block-C' => 'Block-C', 'Block-D' => 'Block-D', 'Block-E' => 'Block-E', 'Block-F' => 'Block-F'],
            ])
            ->add('categories', EntityType::class, [
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'required'      => true,
                'expanded'      =>true,
                'multiple'      =>true,
                'class' => Category::class,
                'choice_label' => 'name',
                'attr'=>['class'=>''],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('s')
                    ->andWhere("s.status = 1")
                    ->andWhere("s.level = 2")
                    ->orderBy('s.name','ASC'),
            ])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('isBrand')

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => 'App\Content\Entity\MallConnect'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_mallconnect';
    }
}
