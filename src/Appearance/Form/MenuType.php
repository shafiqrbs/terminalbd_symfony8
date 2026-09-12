<?php

namespace App\Appearance\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\Menu;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MenuType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('menu',TextType::class, ['attr'=>['class'=>'form-control span12 m-wrap','placeholder'=>'Enter menu name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('slug',TextType::class, ['attr'=>['class'=>'form-control span12 m-wrap','placeholder'=>'Enter menu slug name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('status');

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Menu::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_appearancebundle_menu';
    }
}
