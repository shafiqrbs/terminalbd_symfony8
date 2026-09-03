<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Content\Entity\Page;
use App\Tool\Entity\Module;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class SidebarWidgetType extends AbstractType
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
            ->add('page', EntityType::class, [
                'required'      => true,
                'multiple'      =>true,
                'expanded'      =>true,
                'class'         => Page::class,
                'property'      => 'name',
                'attr'          =>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                ->where("e.status = 1")
                ->andWhere("e.module = 'page'")
                ->orderBy('e.name','ASC')
            ])
            ->add('module', EntityType::class, [
                'required'      => true,
                'multiple'      =>true,
                'expanded'      =>true,
                'class'         => Module::class,
                'property'      => 'name',
                'attr'          =>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('c')
                ->andWhere("c.status = 1")
                ->orderBy('c.id','ASC')
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
            'data_class' => 'App\Tool\Entity\SidebarWidget'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_sidebarwidget';
    }
}
