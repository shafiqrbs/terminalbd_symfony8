<?php

namespace App\Appearance\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Appearance\Entity\Menu;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\Feature;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PageFeatureType extends AbstractType
{

    public function __construct(private readonly GlobalOption $globalOption)
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'span12 m-wrap','placeholder'=>'Enter full name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'span12 m-wrap']])
            ->add('menu', EntityType::class, [
                'required'    => false,
                'class' => Menu::class,
                'empty_value' => '---Select page---',
                'property' => 'menu',
                'attr'=>['class'=>'m-wrap span12 '],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->orderBy('e.menu','ASC'),
            ])
            ->add('customUrl',TextType::class, ['attr'=>['class'=>'span12 m-wrap','placeholder'=>'Custom URL path']])
            ->add('buttonName',TextType::class, ['attr'=>['class'=>'span12 m-wrap','placeholder'=>'Button Text']])
            ->add('buttonBg',TextType::class, ['attr'=>['class'=>'span10 m-wrap colorpicker-default','placeholder'=>'Button Bg']])
            ->add('captionBgColor',TextType::class, ['attr'=>['class'=>'span11 m-wrap colorpicker-default','placeholder'=>'Caption Background Color']])
            ->add('captionFontColor',TextType::class, ['attr'=>['class'=>'span11 m-wrap colorpicker-default','placeholder'=>'Caption Font Color']])

            ->add('captionPosition', ChoiceType::class, [
                'attr'=>['class'=>'span12  m-wrap'],
                'empty_value' => '---Caption Position ---',
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => [
	                'top-left'               => 'Top Left',
	                'top-center'             => 'Top Center',
	                'top-right'              => 'Top Right',
	                'center-left'            => 'Center Left',
	                'center-center'          => 'Center Center',
	                'center-right'           => 'Center Right',
	                'bottom-left'            => 'Bottom Left',
	                'bottom-center'          => 'Bottom Center',
	                'bottom-right'           => 'Bottom Right',

                ],
            ])
            ->add('targetTo', ChoiceType::class, [
                'attr'=>['class'=>'span12  m-wrap targetTo'],
                'empty_value' => '---Select Target URL ---',
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => [
                    'Page'              => 'Page',
                ],
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Feature::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_appearancebundle_feature';
    }


}
