<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Icon;
use App\Content\Entity\ModuleCategory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Content\Entity\Page;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ServiceType extends AbstractType
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
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips','placeholder'=>'Enter service name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ]
            ])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'span12 m-wrap','rows'=>8]])
            ->add('icon', EntityType::class, [
                'required'    => false,
                'class' => Icon::class,
                'placeholder' => '---Select icon---',
                'choice_label' => 'name',
                'attr'=>['class'=>'select2 span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->where("o.status = 1")
                    ->orderBy('o.name','ASC'),
            ])
            ->add('moduleCategory', EntityType::class, [
                'required'    => false,
                'expanded'      =>true,
                'multiple'      =>true,
                'class' => ModuleCategory::class,
                'placeholder' => '---Select category---',
                'choice_label' => 'name',
                'attr'=>['class'=>'check-list  span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->join('o.module', 'module')
                    ->where("o.status = 1")
                    /* ->andWhere(':module MEMBER OF o.module')*/
                    ->andWhere("module.slug = :module")
                    ->setParameter('module', 'service')
                    ->andWhere("o.globalOption =".$this->globalOption->getId())
                    ->orderBy('o.name','ASC'),
            ]);
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
