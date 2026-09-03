<?php

namespace App\Media\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Module;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Media\Entity\PhotoGallery;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class PhotoGalleryType extends AbstractType
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
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Enter title'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('module', EntityType::class, [
                'expanded'      =>true,
                'multiple'      =>true,
                'constraints' => [
                    new NotBlank(message: 'Please input required')
                ],
                'class' => Module::class,
                'property' => 'name',
                'attr'=>['class'=>'check-list span6'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->join('o.siteSettings','m')
                    ->where("o.status = 1")
                    ->andWhere("m.globalOption =".$this->globalOption->getId())
                    ->orderBy('o.name','ASC'),
            ])
            ->add('description',TextareaType::class, ['attr'=>['class'=>'span12', 'row' => 8]])
            ->add('file')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PhotoGallery::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_mediabundle_photogallery';
    }
}
