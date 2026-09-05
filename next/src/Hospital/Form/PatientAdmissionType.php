<?php

namespace App\Hospital\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PatientAdmissionType extends AbstractType
{
    protected $emCategory;


    /** @var  LocationRepository */
    private $location;

    /** @var  GlobalOption */
    private $globalOption;

    /** @var  InvoiceRepository */
    private $admission;

    /** @var  HmsCategoryRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->admission = $options['admission'];
        $this->location = $options['location'];
        $this->emCategory = $options['emCategory'];

        $id = $options['data']->getId();
        $builder
            ->add('disease', TextareaType::class,
                array('attr'=>array('class'=>'m-wrap span12','required'=> false,'rows' => 4,'placeholder'=>'Add disease')))

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
                        ->orderBy("b.name", "ASC");
                }
            ))
            ->add('diseasesProfile', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Hospital\Entity\HmsServiceGroup',
                'choice_label' => 'name',
                'placeholder' => '---Select diseases profile---',
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
            ->add('department', EntityType::class, array(
                'required'    => false,
                'placeholder' => '---Select department---',
                'attr'=>array('class'=>'m-wrap span12 select2'),
                'class' => 'App\Hospital\Entity\HmsCategory',
                'choice_label' => 'nestedLabel',
                'constraints' =>array(
                    new NotBlank(array('message'=>'Select department')),
                ),
                'choices'=> $this->DepartmentChoiceList()
            ))

            ->add('assignDoctor', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Hospital\Entity\Particular',
                'choice_label' => 'doctor',
                'constraints' =>array(
                    new NotBlank(array('message'=>'Select Consultant/Doctor')),
                ),
                'attr'=>array('class'=>'span12 select2 m-wrap'),
                'placeholder' => '--- Choose assign doctor ---',
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('b')
                        ->where("b.status = 1")
                        ->andWhere("b.service = 5")
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
        $builder->add('customer', CustomerForHospitalAdmissionType::class, ['location' => $this->location]);
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'admission' => null,
            'location' => null,
            'emCategory' => null,
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
