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
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\User\Entity\Group;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\User;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserFormType extends AbstractType
{

    /**
     * @param bool   $newEntry
     */
    public function __construct(private $newEntry = true)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fullName', TextType::class, ['class' => 'm-wrap placeholder-no-fix']);

        if($this->newEntry){
            $builder
                ->add('username', null, ['label' => 'form.username', 'translation_domain' => 'FOSUserBundle'])
                ->add('plainPassword', RepeatedType::class, [
                    'type' => 'password',
                    'options' => ['translation_domain' => 'FOSUserBundle'],
                    'first_options' => ['label' => 'form.password'],
                    'second_options' => ['label' => 'form.password_confirmation'],
                    'invalid_message' => 'fos_user.password.mismatch',
                ]);
        }

        $builder
            ->add('email', EmailType::class, ['label' => 'form.email', 'translation_domain' => 'FOSUserBundle'])
            ->add('groups', EntityType::class, [
                'class' => Group::class,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('g')
                    ->andWhere("g.name != :group")
                    ->setParameter('group', 'Super Administrator'),
                'property' => 'name',
                'multiple' => true,
            ])
            ->add('profile', ProfileFormType::class)
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'validation_groups'  => 'registration',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'manage_user';
    }
}
