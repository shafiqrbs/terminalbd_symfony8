<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Syndicate;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\SyndicateModule;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SyndicateModuleType extends AbstractType
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
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])

            ->add('moduleClass',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter module class name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])

            ->add('menu',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter menu name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('description',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'', 'row'=> 5] ])

            ->add('syndicates', EntityType::class, [
                'required'      => true,
                'expanded'      =>true,
                'multiple'      =>true,
                'class' => Syndicate::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span4 check-list'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('s')
                    ->andWhere("s.status = 1")
                    ->andWhere("s.level = 2")
                    ->orderBy('s.name','ASC'),
            ])
            ->add('isHome')
            ->add('status')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SyndicateModule::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_syndicatemodule';
    }
}
