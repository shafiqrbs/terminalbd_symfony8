<?php

namespace App\Hospital\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\DomainUser\Form\CustomerForHospitalAdmissionType;
use App\DomainUser\Form\CustomerForHospitalNewAdmissionType;
use App\DomainUser\Form\CustomerForHospitalType;
use App\DomainUser\Form\CustomerType;
use App\Hospital\Entity\Category;
use App\Hospital\Entity\HmsCategory;
use App\Hospital\Repository\CategoryRepository;
use App\Hospital\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class NewPatientAdmissionType extends AbstractType
{
    protected $cabins;


    /** @var  LocationRepository */
    private $location;

    /** @var  HmsCategoryRepository */
    private $emCategory;

    /** @var  GlobalOption */
    private $globalOption;

    /** @var  array */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->emCategory = $options['emCategory'];
        $this->location = $options['location'];
        $this->cabins = $options['cabins'];

        $builder

            ->add('disease', TextareaType::class, array('attr'=>array('class'=>'m-wrap span12','rows' => 6,'placeholder'=>'Disease description'),
            ))
            ->add('cabin', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'choice_label' => 'name',
                'group_by'  => 'serviceGroup.name',
                'choice_translation_domain' => true,
                'attr'=>array('class'=>'span12 select2 invoiceCabinBooking m-wrap'),
                'placeholder' => '---Select cabin/ward no---',
                'constraints' =>array(
                    new NotBlank(message: 'Please select cabin/ward no')
                ),
                'query_builder' => function(EntityRepository $er){
                    $qb = $er->createQueryBuilder('b');
                    $qb  ->where("b.status = 1");
                    $qb  ->andWhere("b.service = 2");
                   /* if($this->cabins){
                        $qb    ->andWhere('b.id NOT IN (:ids)')->setParameter('ids', $this->cabins );
                    }*/
                    $qb ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId());
                    $qb ->orderBy("b.name", "ASC");
                    return $qb;
                }
            ))
            ->add('diseasesProfile', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Hospital\Entity\HmsServiceGroup',
                'choice_label' => 'name',
                'placeholder' => '---Choose a diseases profile---',
                'attr'=>array('class'=>'span12 m-wrap select2'),
                'constraints' =>array(
                    new NotBlank(message: 'Select diseases profile'),
                ),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                        ->andWhere("e.service = 11")
                        ->andWhere("e.status = 1")
                        ->orderBy("e.name","ASC");
                }
            ))
            ->add('department', EntityType::class, array(
                'required'    => true,
                'placeholder' => '---Choose a department---',
                'attr'=>array('class'=>'m-wrap span12 select2'),
                'class' => 'App\Hospital\Entity\HmsCategory',
                'choice_label' => 'nestedLabel',
                'choices'=> $this->DepartmentChoiceList()
            ))

            ->add('referredDoctor', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'choice_label' => 'referredName',
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'placeholder' => '--- Choose Referred ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere('b.service IN(:service)') ->setParameter('service',array_values(array(5,6)))
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.particularCode", "ASC");
                }
            ))

            ->add('assignDoctor', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Hospital\Entity\Particular',
                'choice_label' => 'doctor',
                'constraints' =>array(
                    new NotBlank(message: 'Select Consultant/Doctor'),
                ),
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'placeholder' => '--- Choose assign doctor ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere('b.service IN(:service)') ->setParameter('service',array_values(array(5)))
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))

            ->add('anesthesiaDoctor', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'choice_label' => 'doctor',
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'placeholder' => '--- Choose anesthesia doctor ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.service = 5")
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))
            ->add('assistantDoctor', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'choice_label' => 'doctor',
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'placeholder' => '--- Choose assistant doctor ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.service = 5")
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))
        ;
        if( $this->globalOption->getHospitalConfig()->isMarketingExecutive() == 1){

            $builder->add('marketingExecutive', EntityType::class, array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'choice_label' => 'marketingExecutiveEmployee',
                'attr'=>array('class'=>'span12 select2 m-wrap marketingExecutive'),
                'placeholder' => '--- Choose Marketing Executive ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere('b.service IN(:service)') ->setParameter('service',array_values(array(14)))
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.name", "ASC");
                }
            ));
        }
        $builder->add('customer', CustomerForHospitalNewAdmissionType::class, ['location' => $this->location]);
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'emCategory' => null,
            'location' => null,
            'cabins' => null,
        ]);

        $resolver->setDefaults(array(
            'data_class' => 'App\Hospital\Entity\Invoice'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'invoice';
    }

    /**
     * @return mixed
     */
    protected function CabinChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 2 /** Pathology */ );

    }

    /**
     * @return mixed
     */
    protected function DepartmentChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 7 /** Department */);

    }

}
