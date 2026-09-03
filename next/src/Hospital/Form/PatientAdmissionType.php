<?php

namespace App\Hospital\Form;

use App\DomainUser\Form\CustomerForHospitalAdmissionType;
use App\DomainUser\Form\CustomerForHospitalType;
use App\DomainUser\Form\CustomerType;
use App\Hospital\Entity\Category;
use App\Hospital\Entity\HmsCategory;
use App\Hospital\Repository\CategoryRepository;
use App\Hospital\Repository\HmsCategoryRepository;
use App\Hospital\Repository\InvoiceRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PatientAdmissionType extends AbstractType
{

    /** @var  LocationRepository */
    private $location;

    /** @var  GlobalOption */
    private $globalOption;

    /** @var  InvoiceRepository */
    private $admission;

    /** @var  HmsCategoryRepository */
    private $emCategory;

    function __construct(GlobalOption $globalOption, InvoiceRepository $admission, LocationRepository $location, HmsCategoryRepository $emCategory)
    {
        $this->admission        = $admission;
        $this->globalOption     = $globalOption;
        $this->location         = $location;
        $this->emCategory       = $emCategory;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $id = $options['data']->getId();
        $builder
            ->add('disease','textarea',
                array('attr'=>array('class'=>'m-wrap span12','required'=> false,'rows' => 4,'placeholder'=>'Add disease')))

            ->add('referredDoctor', 'entity', array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'property' => 'referredName',
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'empty_value' => '--- Choose Referred ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere('b.service IN(:service)') ->setParameter('service',array_values(array(5,6)))
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))
            ->add('diseasesProfile', 'entity', array(
                'required'    => true,
                'class' => 'App\Hospital\Entity\HmsServiceGroup',
                'property' => 'name',
                'empty_value' => '---Select diseases profile---',
                'attr'=>array('class'=>'span12 m-wrap select2'),
                'constraints' =>array(
                    new NotBlank(array('message'=>'Select diseases profile')),
                ),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where('e.hospitalConfig ='.$this->globalOption->getHospitalConfig()->getId())
                        ->andWhere("e.service = 11")
                        ->andWhere("e.status = 1")
                        ->orderBy("e.name","ASC");
                }
            ))
            ->add('department', 'entity', array(
                'required'    => false,
                'empty_value' => '---Select department---',
                'attr'=>array('class'=>'m-wrap span12 select2'),
                'class' => 'App\Hospital\Entity\HmsCategory',
                'property' => 'nestedLabel',
                'constraints' =>array(
                    new NotBlank(array('message'=>'Select department')),
                ),
                'choices'=> $this->DepartmentChoiceList()
            ))

            ->add('assignDoctor', 'entity', array(
                'required'    => true,
                'class' => 'App\Hospital\Entity\Particular',
                'property' => 'doctor',
                'constraints' =>array(
                    new NotBlank(array('message'=>'Select Consultant/Doctor')),
                ),
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'empty_value' => '--- Choose assign doctor ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.service = 5")
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))
            ->add('anesthesiaDoctor', 'entity', array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'property' => 'doctor',
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'empty_value' => '--- Choose anesthesia doctor ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.service = 5")
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.name", "ASC");
                }
            ))
            ->add('assistantDoctor', 'entity', array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'property' => 'doctor',
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'empty_value' => '--- Choose assistant doctor ---',
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

            $builder->add('marketingExecutive', 'entity', array(
                'required'    => false,
                'class' => 'App\Hospital\Entity\Particular',
                'property' => 'marketingExecutiveEmployee',
                'attr'=>array('class'=>'span12 select2 m-wrap marketingExecutive'),
                'empty_value' => '--- Choose Marketing Executive ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere('b.service IN(:service)') ->setParameter('service',array_values(array(14)))
                        ->andWhere("b.hospitalConfig =".$this->globalOption->getHospitalConfig()->getId())
                        ->orderBy("b.name", "ASC");
                }
            ));
        }
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into CustomerForHospitalAdmissionType::configureOptions() and pass them as form options: $this->location 
        $builder->add('customer', new CustomerForHospitalAdmissionType( $this->location ));
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\Hospital\Entity\Invoice'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'invoice';
    }

    /**
     * @return mixed
     */
    protected function CabinChoiceList( $id)
    {
        return $this->admission->getExistCabin($this->globalOption->getHospitalConfig()->getId(),$id);

    }

    /**
     * @return mixed
     */
    protected function DepartmentChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 7 /** Department */);

    }


}
