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
use App\Tool\Entity\Module;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BlogType extends AbstractType
{

    private $module;
    private $globalOption;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->module = $options['module'];
        $this->globalOption = $options['globalOption']->getId();
        $this->module = $options['module']->getId();

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter title name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('authorName',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter author name']])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter author designation']])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'span12 ckeditor m-wrap','rows'=>15]])
            ->add('category', EntityType::class, [
                'required'    => false,
                'class' => ModuleCategory::class,
                'placeholder' => '---Select Category---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
	                    ->join('o.module', 'module')
	                    ->where("o.status = 1")
	                    ->andWhere("module.slug = :module")
	                    ->setParameter('module', 'products')
                    ->andWhere("o.globalOption =".$this->globalOption)
                    ->orderBy('o.name','ASC'),
            ])
            ->add('photo_gallery', EntityType::class, [
                'required'    => false,
                'class' => PhotoGallery::class,
                'placeholder' => '---Select Photo Gallery---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->andWhere("o.status = 1")
                    ->andWhere("o.globalOption = $this->globalOption")
                    ->orderBy('o.name','ASC'),
            ])
            ->add('status')

        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'module' => null,
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
