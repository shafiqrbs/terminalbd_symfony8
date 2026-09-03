<?php

/*
 * This file is part of the Docudex project.
 *
 * (c) Mohammad Emran Hasan <phpfour@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Group Form
 *
 * Small description about GroupFormType.
 *
 * @author Mohammad Emran Hasan <phpfour@gmail.com>
 */
class GroupFormType extends AbstractType
{
    public function __construct(private $class, private $permissionBuilder)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', null, [
            'label' => 'Name',
            'attr'  => ['class' => 'span5']
        ]);

        $builder->add('description', TextareaType::class, [
            'label'    => 'Description',
            'required' => false,
            'attr'     => ['class' => 'span5', 'rows' => 3]
        ]);

        $builder->add('roles', ChoiceType::class, [
            'choices'  => $this->permissionBuilder->getPermissionHierarchyForChoiceField(),
            'multiple' => true,
            'attr'     => ['class' => 'span5', 'size' => 15]
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->class,
            'intention'  => 'group',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'fos_user_group';
    }
}