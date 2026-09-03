<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Content\Entity\HomePage;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class HomePageType extends AbstractType
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
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter name '],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('showAbout')
            ->add('featureText',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>4]])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'wysihtml5 m-wrap span12','rows'=>10]])
            /*
           ->add('file','file', array('attr'=>array('class'=>'default')))
           ->add('photo_gallery', 'entity', array(
               'required'    => false,
               'class' => 'App\Media\Entity\PhotoGallery',
               'empty_value' => '---Select Photo Gallery---',
               'property' => 'name',
               'attr'=>array('class'=>'m-wrap select2 span12'),
               'query_builder' => function(EntityRepository $er){
                       return $er->createQueryBuilder('o')
                           ->andWhere("o.status = 1")
                           ->andWhere("o.globalOption = $this->globalOption")
                           ->orderBy('o.name','ASC');
                   },
           ))

          ->add('syndicateModules', 'entity', array(
               'required'      => true,
               'expanded'      =>true,
               'multiple'      =>true,
               'class' => 'App\Tool\Entity\SyndicateModule',
               'property' => 'name',
               'attr'=>array('class'=>'check-list span12'),
               'query_builder' => function(EntityRepository $er){
                       return $er->createQueryBuilder('sm')
                           ->where("sm.status = 1")
                           ->andWhere("sm.isHome = 1")
                           ->andWhere(':siteSetting MEMBER OF sm.siteSettings')
                           ->setParameter('siteSetting',$this->siteSettingId)
                           ->orderBy('sm.name','ASC');

                   },

           ))
           ->add('modules', 'entity', array(
               'required'      => true,
               'expanded'      =>true,
               'multiple'      =>true,
               'class' => 'App\Tool\Entity\Module',
               'property' => 'name',
               'attr'=>array('class'=>'check-list span12'),
               'query_builder' => function(EntityRepository $er){
                       return $er->createQueryBuilder('sm')
                           ->where("sm.status = 1")
                           ->andWhere("sm.isHome = 1")
                           ->andWhere(':siteSetting MEMBER OF sm.siteSettings')
                           ->setParameter('siteSetting',$this->siteSettingId)
                           ->orderBy('sm.name','ASC');
                   },

           ))
           ->add('showingListing', 'choice', array(
               'choices'  => array(3 => 3, 5 => 5),
               'attr'=>array('class'=>'radio-list'),
               'expanded' => true,
               'multiple' => false,
               'data'     => 1

           ))*/

        ;

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => HomePage::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_homepage';
    }
}
