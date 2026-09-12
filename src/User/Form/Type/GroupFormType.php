<?php

declare(strict_types=1);

namespace App\User\Form\Type;

use App\User\Entity\Group;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Replaces the form built by fos_user.group.form.factory.
 * FOSUserBundle's GroupFormType exposed the name only; roles are assigned
 * through the app's own category-access screen.
 */
class GroupFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, [
            'label' => 'form.group.name',
            'translation_domain' => 'FOSUserBundle',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Group::class,
            'csrf_token_id' => 'group',
            'empty_data' => fn () => new Group(''),
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'fos_user_group';
    }
}
