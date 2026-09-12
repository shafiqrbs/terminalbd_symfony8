<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DomainUser\Entity\Customer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomerForMedicineType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs','autocomplete'=>'off','placeholder'=>'Enter customer name']])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 select2mobile inputs mobile','autocomplete'=>'off','placeholder'=>'Enter mobile no']])
            ->add('address',TextareaType::class, ['attr'=>['required'=>false ,'class'=>'m-wrap span12 resize inputs address','rows'=> 3,'autocomplete'=>'off','placeholder'=>'Enter patient address']
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_domainuserbundle_customer';
    }


}
