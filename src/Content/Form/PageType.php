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
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];


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
                'placeholder' => '---Select Gallery Position---',
                'choices' => ['Top' => 'top', 'Bottom' => 'bottom']
            ])
            ->add('galleryType', ChoiceType::class, [
                'attr'=>['class'=>'span12'],
                'required'    => false,
                'placeholder' => '---Select Gallery Type---',
                'choices' => ['Thumb' => 'thumb', 'Carousel' => 'carousel', 'Thumb Slider' => 'thumbSlider'],
            ])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'span12 ckeditor m-wrap textarea-large','rows' => 30]])
            ->add('shortDescription',TextareaType::class, ['attr'=>['class'=>'span12 m-wrap','rows' => 5]])

            ->add('parent', EntityType::class, [
                'required'    => false,
                'class' => Page::class,
                'placeholder' => '---Select parent page---',
                'choice_label' => 'name',
                'attr'=>['class'=>'select2 span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->where("o.status = 1")
                    ->andWhere("o.globalOption =".$this->globalOption->getId())
                    ->orderBy('o.name','ASC'),
            ])
            ->add('icon', EntityType::class, [
                'required'    => false,
                'class' => Icon::class,
                'placeholder' => '---Select Icon---',
                'choice_label' => 'name',
                'attr'=>['class'=>'select2 span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->where("o.status = 1")
                    ->orderBy('o.name','ASC'),
            ])

            ->add('photo_gallery', EntityType::class, [
                'required'    => false,
                'class' => PhotoGallery::class,
                'placeholder' => '---Select Photo Gallery---',
                'choice_label' => 'name',
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
            'globalOption' => null,
        ]);

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
