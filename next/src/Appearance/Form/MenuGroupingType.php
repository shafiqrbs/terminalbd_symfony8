<?php

namespace App\Appearance\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\MenuGrouping;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MenuGroupingType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

           /* ->add('menuGroup', 'entity', array(
                'empty_value' => '---Select Menu Group---',
                'required'    => true,
                'attr'=>array('class'=>'form-control input-sm select2'),
                'class' => \App\Appearance\Entity\MenuGroup::class,
                'property' => 'name'
            ))*/
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuGrouping::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_appearancebundle_menugrouping';
    }
}
