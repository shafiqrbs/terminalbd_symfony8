<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Hospital\Entity\Service;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\HmsServiceGroup;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ServiceGroupType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter service group name'],
                    'constraints' =>[
                        new NotBlank(message: 'Enter service group name')
                    ]]
            )
            ->add('service', EntityType::class, [
                'required'    => false,
                'class' => Service::class,
                'property' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'empty_value' => '---Select service group---',
                'constraints' =>[
                    new NotBlank(message: 'Select service type')
                ]
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HmsServiceGroup::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_hmsservice_group';
    }


}
