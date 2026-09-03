<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Content\Entity\Page;
use App\Tool\Entity\Icon;
use App\Media\Entity\PhotoGallery;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PageType extends AbstractType
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
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips','placeholder'=>'Enter Name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])

            ->add('menu',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter menu'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('galleryPosition', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'required'    => false,
                'empty_value' => '---Select Gallery Position---',
                'choices' => ['top' => 'Top','bottom' => 'Bottom']
            ])
            ->add('galleryType', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'required'    => false,
                'empty_value' => '---Select Gallery Type---',
                'choices' => ['thumb' => 'Thumb','carousel' => 'Carousel','thumbSlider' => 'Thumb Slider'],
            ])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'span12 ckeditor m-wrap textarea-large','rows' => 30]])
            ->add('shortDescription',TextareaType::class, ['attr'=>['class'=>'span12 m-wrap','rows' => 5]])

            ->add('parent', EntityType::class, [
                'required'    => false,
                'class' => Page::class,
                'empty_value' => '---Select parent page---',
                'property' => 'name',
                'attr'=>['class'=>'select2 span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->where("o.status = 1")
                    ->andWhere("o.globalOption =".$this->globalOption->getId())
                    ->orderBy('o.name','ASC'),
            ])
            ->add('icon', EntityType::class, [
                'required'    => false,
                'class' => Icon::class,
                'empty_value' => '---Select Icon---',
                'property' => 'name',
                'attr'=>['class'=>'select2 span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->where("o.status = 1")
                    ->orderBy('o.name','ASC'),
            ])

            ->add('photo_gallery', EntityType::class, [
                'required'    => false,
                'class' => PhotoGallery::class,
                'empty_value' => '---Select Photo Gallery---',
                'property' => 'name',
                'attr'=>['class'=>'select2 span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->where("o.status = 1")
                    ->andWhere("o.globalOption =".$this->globalOption->getId())
                    ->orderBy('o.name','ASC'),
            ])
            ->add('status')
            ->add('removeImage')

        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_page';
    }
}
