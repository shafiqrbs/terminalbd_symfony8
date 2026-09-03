<?php

namespace App\Content\Form;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Content\Entity\SiteContent;
use App\Media\Entity\PhotoGallery;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SiteContentType extends AbstractType
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
                    new Length(max: 200)
                ]
            ])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'ckeditor m-wrap span12','rows'=>15]])
	        ->add('businessSector', ChoiceType::class, [
		        'attr'=>['class'=>'m-wrap span12'],
		        'choices' => ['portal' => 'Portal','education' => 'Education',  'ecommerce' => 'E-commerce', 'reservation' => 'Reservation', 'fleet' => 'Fleet Management'],
	        ])

	        ->add('parent', EntityType::class, [
                'required'    => false,
                'class' => SiteContent::class,
                'empty_value' => '---Select parent page---',
                'property' => 'name',
                'attr'=>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->andWhere("o.status = 1")
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

	        ->add('isPage')
            ->add('status');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteContent::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_sitecontent';
    }
}
