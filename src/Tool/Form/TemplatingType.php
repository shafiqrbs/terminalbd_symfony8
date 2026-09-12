<?php

namespace App\Tool\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\Templating;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TemplatingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('backgroundColor')
            ->add('headerColor')
            ->add('footerColor')
            ->add('bodyColor')
            ->add('menuColor')
            ->add('menuLiColor')
            ->add('menuLiHoverColor')
            ->add('siteFontSize')
            ->add('siteFontFamily')
            ->add('anchorColor')
            ->add('anchorHoverColor')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Templating::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_templating';
    }
}
