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

class ProfileFormType extends AbstractType
{
    protected $edit;


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->edit = $options['edit'];

           $builder
                ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap placeholder-no-fix mobile','placeholder'=>'Enter your mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
           ]])
               ->add('file')
               ->add('designation');
           ;

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'edit' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Profile::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'manage_profile';
    }
}
