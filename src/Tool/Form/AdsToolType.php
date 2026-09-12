<?php

namespace App\Tool\Form;

use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\AdsTool;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class AdsToolType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('googleServiceID')
            ->add('slotID')
            ->add('siteName')
            ->add('keyword')
            ->add('metaDescription')
            ->add('googleVerificationCode')
            ->add('redirectLang')
            ->add('redirectCode')
            ->add('statCounterID')
            ->add('statCounterSecurityCode')
            ->add('statCounterVisible')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AdsTool::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_adstool';
    }
}
