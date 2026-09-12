<?php

namespace App\User\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class VendorProfileType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your full name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Enter mobile number', 'data-original-title' =>'Must be use personal mobile number.' , 'data-trigger' => 'hover'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter address']])
            ->add('permanentAddress',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter permanent address']])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter designation number']])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your email address']])
            ->add('profession',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter profession number']])
            ->add('nid',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter national id card no']])
            ->add('dob',BirthdayType::class, ['attr'=>['class'=>'m-wrap span6']])
            ->add('about',TextareaType::class, ['attr'=>['class'=>'m-wrap span12']])
            ->add('bloodGroup', ChoiceType::class, [
                'attr'=>['class'=>''],
                'choices' => ['A+' => 'A+', 'A-' => 'A-'],
            ])
            ->add('interest',TextType::class, ['attr'=>['class'=>'m-wrap span12']])
            ->add('file')

        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'manage_profile';
    }
}