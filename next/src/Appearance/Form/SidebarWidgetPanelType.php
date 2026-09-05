<?php

namespace App\Appearance\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Content\Entity\Page;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\Module;
use App\Appearance\Entity\Feature;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\SidebarWidgetPanel;
use App\Tool\Entity\GlobalOption;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SidebarWidgetPanelType extends AbstractType
{
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('page', EntityType::class, [
                'required'    => false,
                'multiple'      =>true,
                'class' => Page::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12 multiselect'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.module = 1")
                    ->andWhere("e.globalOption = $this->globalId")
                    ->orderBy('e.name','ASC'),
            ])

            ->add('module', EntityType::class, [
                'required'    => false,
                'multiple'      =>true,
                'class' => Module::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12 multiselect'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere('e.id IN(:ids)')
                    ->setParameter('ids',array_values($this->modules))
                    ->orderBy('e.name','ASC'),
            ])

            ->add('feature', EntityType::class, [
                'required'    => false,
                'multiple'      =>true,
                'class' => Feature::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12 multiselect'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy('e.name','ASC'),
            ]);
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
            'data_class' => SidebarWidgetPanel::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_appearancebundle_featurewidgetpanel';
    }

}
