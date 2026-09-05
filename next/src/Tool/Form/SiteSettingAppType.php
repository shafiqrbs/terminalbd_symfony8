<?php

namespace App\Tool\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\AppModule;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\SiteSetting;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SiteSettingAppType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('appModules', EntityType::class, [
                'required'      => true,
                'expanded'      =>true,
                'multiple'      =>true,
                'class' => AppModule::class,
                'choice_label' => 'name',
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
        return 'sitesetting';
    }
}
