<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Media\Entity\PhotoGallery;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Content\Entity\SyndicateContent;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SyndicateContentType extends AbstractType
{

    public function __construct(private $globalOption, private $siteSettingId)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'form-control']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'wysihtml5 form-control','rows'=>15]])
            ->add('photo_gallery', EntityType::class, [
                'required'    => false,
                'class' => PhotoGallery::class,
                'empty_value' => '---Select Photo Gallery---',
                'property' => 'name',
                'attr'=>['class'=>'form-control input-sm'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->andWhere("o.status = 1")
                    ->andWhere("o.globalOption = $this->globalOption")
                    ->orderBy('o.name','ASC'),
            ])
            ->add('file')
            ->add('status')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SyndicateContent::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_syndicatecontent';
    }
}
