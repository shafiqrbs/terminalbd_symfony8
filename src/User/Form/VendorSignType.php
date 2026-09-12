<?php

namespace App\User\Form;


use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class VendorSignType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('username',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your user name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter your user name'),
                        new Length(max: 200)
                    ]]
            )
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => ['translation_domain' => 'FOSUserBundle'],
                'first_options' => ['label' => 'form.password'],
                'second_options' => ['label' => 'form.password_confirmation'],
                'invalid_message' => 'fos_user.password.mismatch',
            ])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your secured no on your password'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter your secured password'),
                        new Length(max: 200)
                    ]]
            );
           /* ->add('enabled');*/
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