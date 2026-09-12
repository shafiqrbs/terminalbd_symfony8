<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessBrand;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BusinessBrandType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter business area name'],
                'constraints' =>[
                    new NotBlank(message: 'Enter brand name')
                ]]
            );
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BusinessBrand::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'brand';
    }


}
