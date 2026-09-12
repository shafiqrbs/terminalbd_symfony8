<?php

namespace App\User\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class UserConfirmType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('username',TextType::class, ['attr'=>['class'=>'mobile','placeholder'=>'Enter your mobile no'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter your mobile no'),
                        new Length(max: 200)
                    ]]
            )
            ->add('password',TextType::class, ['attr'=>['class'=>'secured','placeholder'=>'Enter your secured no on your mobile '],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter your secured password'),
                        new Length(max: 200)
                    ]]
            );

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'Core_userbundle_user';
    }
}