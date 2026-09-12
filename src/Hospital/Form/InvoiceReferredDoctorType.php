<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class InvoiceReferredDoctorType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter Referred/agent name'],'required'=> false])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','autocomplete'=>'off','placeholder'=>'Mobile no'],'required'=> false])
            ->add('doctorSignature',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> 4,'autocomplete'=>'off','placeholder'=>'Enter Doctor signature'],'required'=> false]
            )
            ->add('isDoctor',CheckboxType::class,  ['attr'=> ['class'=>'custom-control-input'],'required'=> false])
        ;

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Particular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_referred_doctor';
    }



}
