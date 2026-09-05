<?php

declare(strict_types=1);

namespace App\User\Form\Type;

use App\User\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Validator\Constraints\UserPassword;

/**
 * Replaces FOS\UserBundle\Form\Type\ProfileFormType as a base class.
 *
 * The split between buildForm() and buildUserForm() is kept because
 * UserProfileFormType overrides the latter, exactly as it did against FOS.
 */
abstract class AbstractProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->buildUserForm($builder, $options);

        $constraint = new UserPassword();

        if ($options['validation_groups']) {
            $constraint->groups = $options['validation_groups'];
        }

        $builder->add('current_password', PasswordType::class, [
            'label' => 'form.current_password',
            'translation_domain' => 'FOSUserBundle',
            'mapped' => false,
            'constraints' => $constraint,
            'attr' => ['autocomplete' => 'current-password'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'csrf_token_id' => 'profile',
            'validation_groups' => ['Profile', 'Default'],
        ]);
    }

    protected function buildUserForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', null, [
                'label' => 'form.username',
                'translation_domain' => 'FOSUserBundle',
            ])
            ->add('email', EmailType::class, [
                'label' => 'form.email',
                'translation_domain' => 'FOSUserBundle',
            ]);
    }
}
