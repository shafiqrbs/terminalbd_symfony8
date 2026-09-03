<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class SignupProfileType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
           $builder
                ->add('name',TextType::class,
                    ['required' => true,'mapped'=>false,'attr'=>['class'=>'m-wrap span12','mapped'=>false,'placeholder'=>'Enter your full name'],
                'constraints' =>[
                    new NotBlank(message: 'Enter your full name required'),
                    new Length(max: 200)
                    ]]
                )
                ->add('username',TextType::class, ['required' => true,'mapped' => false,'attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your full name'],
                'constraints' =>[
                    new NotBlank(message: 'Enter user name required'),
                    new Length(max: 200)
                    ]]
                )
                ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap  mobile span12','placeholder'=>'Enter your mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Enter your mobile no required'),
                    new Length(max: 200)
                    ]]
                )

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
