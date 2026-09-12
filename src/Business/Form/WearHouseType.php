<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\WearHouse;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class WearHouseType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter wear house name'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter wear house name')
                ]]
            )
            ->add('shortCode',TextType::class, ['attr'=>['class'=>'m-wrap span12','maxlength'=>3,'autocomplete'=>'off','placeholder'=>'Enter short code 3 digit'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter short code 3 digit')
                ]]
            );
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WearHouse::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'wearhouse';
    }


}
