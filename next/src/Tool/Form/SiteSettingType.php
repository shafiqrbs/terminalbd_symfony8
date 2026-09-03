<?php

namespace App\Tool\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Theme;
use App\Tool\Entity\Module;
use App\Tool\Entity\AppModule;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\SiteSetting;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiteSettingType extends AbstractType
{

    public function __construct(public $syndicateId)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {



        $builder


              ->add('theme', EntityType::class, [
                 'required'    => false,
                 'class' => Theme::class,
                 'empty_value' => '---Select Theme ---',
                 'property' => 'name',
                 'attr'=>['class'=>'m-wrap span12'],
                 'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('wt')
                     ->andWhere("wt.status = 1")
                     /*->andWhere(':syndicate MEMBER OF wt.syndicates')
                     ->setParameter('syndicate', $this->syndicateId)*/
                     ->orderBy('wt.name','ASC'),
              ])

              /*->add('syndicateModules', 'entity', array(
                  'required'      => true,
                  'expanded'      =>true,
                  'multiple'      =>true,
                  'class' => 'App\Tool\Entity\SyndicateModule',
                  'property' => 'name',
                  'attr'=>array('class'=>'check-list span12'),
                  'query_builder' => function(EntityRepository $er){
                          return $er->createQueryBuilder('sm')
                              ->andWhere("sm.status = 1")
                              ->andWhere(':syndicate MEMBER OF sm.syndicates')
                              ->orderBy('sm.name','ASC')
                              ->setParameter('syndicate', $this->syndicateId);
                      },

              ))*/

            ->add('modules', EntityType::class, [
                'required'      => true,
                'expanded'      =>true,
                'multiple'      =>true,
                'class' => Module::class,
                'property' => 'name',
                'attr'=>['class'=>'check-list span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('m')
                    ->where("m.status = 1")
                    ->andWhere("m.slug != 'contact'")
                    ->andWhere("m.slug != 'page'")
                    ->orderBy('m.name','ASC'),
            ])

            ->add('appModules', EntityType::class, [
                'required'      => true,
                'expanded'      =>true,
                'multiple'      =>true,
                'class' => AppModule::class,
                'property' => 'name',
                'attr'=>['class'=>'check-list span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('m')
                    ->andWhere("m.status = 1")
                    ->orderBy('m.name','ASC'),
            ]);


    }


    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteSetting::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_sitesetting';
    }
}
