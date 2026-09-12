<?php

namespace App\Medicine\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Medicine\Entity\MedicineVendor;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class VendorType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter vendor name']])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter mobile no']])
            ->add('tpPercent',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter TP Percent']])
            ->add('companyName',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter company name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')]
            ])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter vendor address']])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter email address']]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MedicineVendor::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'vendor';
    }
}
