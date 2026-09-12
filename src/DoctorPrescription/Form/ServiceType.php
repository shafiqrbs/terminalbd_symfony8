<?php

namespace App\DoctorPrescription\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DoctorPrescription\Entity\DpsService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ServiceType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter service name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter particular name')
                    ]]
            )
            ->add('serviceFormat', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap select-custom'],
                'expanded'      =>false,
                'multiple'      =>false,
                'placeholder' => '---Choose service format---',
                'constraints' =>[
                    new NotBlank(message: 'Select service format')
                ],
                'choices' => ['Label Value' => 'label-value', 'Text Field' => 'text-field', 'Checkbox with Text Field' => 'checkbox-text-field', 'Checkbox' => 'checkbox', 'Text-area' => 'textarea', 'File Upload' => 'file'],
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
            'data_class' => DpsService::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_dps_service';
    }


}
