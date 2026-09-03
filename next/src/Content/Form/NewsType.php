<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Content\Entity\ModuleCategory;
use App\Media\Entity\PhotoGallery;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Content\Entity\Page;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class NewsType extends AbstractType
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
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('authorName',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter author name']])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter author designation']])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'ckeditor m-wrap span12','rows'=>8]])
            ->add('moduleCategory', EntityType::class, [
                'expanded'      =>true,
                'multiple'      =>true,
                'required'    => false,
                'class' => ModuleCategory::class,
                'empty_value' => '---Select category page---',
                'property' => 'name',
                'attr'=>['class'=>'check-list span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
	                    ->join('o.module', 'module')
	                    ->where("o.status = 1")
	                    ->andWhere("module.slug = :module")
	                    ->setParameter('module', 'news')
                    ->andWhere("o.globalOption =".$this->globalOption->getId())
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
