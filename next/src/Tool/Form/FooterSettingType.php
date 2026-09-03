<?php

namespace App\Tool\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\FooterSetting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class FooterSettingType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('copyRight')
            ->add('displayHomePage')
            ->add('displaySubPage')
            ->add('displayIcon')
            ->add('phoneDisplayHomePage')
            ->add('phoneDisplaySubPage')
            ->add('phoneDisplayIcon')
            ->add('webDisplayHomePage')
            ->add('webDisplaySubPage')
            ->add('turnOff')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FooterSetting::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_footersetting';
    }
}
