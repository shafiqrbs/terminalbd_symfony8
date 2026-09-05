<?php

declare(strict_types=1);

namespace App\User\Form\Type;

use App\User\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * Replaces the form built by fos_user.change_password.form.factory.
 * Field names, labels, translation domain and the fos_user.password.mismatch
 * message are kept so the vendored _fosuser templates render unchanged.
 */
class ChangePasswordFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('current_password', PasswordType::class, [
                'label' => 'form.current_password',
                'translation_domain' => 'FOSUserBundle',
                'mapped' => false,
                'constraints' => new UserPassword(),
                'attr' => ['autocomplete' => 'current-password'],
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'options' => [
                    'translation_domain' => 'FOSUserBundle',
                    'attr' => ['autocomplete' => 'new-password'],
                ],
                'first_options' => ['label' => 'form.new_password'],
                'second_options' => ['label' => 'form.new_password_confirmation'],
                'invalid_message' => 'fos_user.password.mismatch',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'change_password',
            'validation_groups' => ['ChangePassword', 'Default'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'fos_user_change_password';
    }
}
