<?php

namespace App\Appearance\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\MenuCustom;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class MenuCustomType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('menu',TextType::class, ['attr'=>['class'=>'form-control span12','placeholder'=>'Enter menu name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('slug',TextType::class, ['attr'=>['class'=>'form-control span12','placeholder'=>'Enter menu slug name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuCustom::class
        ]);
    }

    /**
     * @return string
     */
    public function getMenu()
    {
        return 'setting_bundle_appearancebundle_menucustom';
    }
}
