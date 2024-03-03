<?php

namespace Appstore\Bundle\BusinessBundle\Form;

use Appstore\Bundle\BusinessBundle\Entity\BusinessConfig;
use Appstore\Bundle\DomainUserBundle\Form\CustomerForBusinessType;
use Appstore\Bundle\DomainUserBundle\Form\CustomerForHospitalType;
use Appstore\Bundle\DomainUserBundle\Form\CustomerType;
use Appstore\Bundle\HospitalBundle\Entity\Category;
use Appstore\Bundle\HospitalBundle\Entity\HmsCategory;
use Appstore\Bundle\HospitalBundle\Repository\CategoryRepository;
use Appstore\Bundle\HospitalBundle\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use Setting\Bundle\LocationBundle\Repository\LocationRepository;
use Setting\Bundle\ToolBundle\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BusinessBatchParticularType extends AbstractType
{


    /** @var  GlobalOption */
    private $globalOption;

    /** @var $config BusinessConfig */
    private $config;

    function __construct(GlobalOption $globalOption)
    {
        $this->globalOption     = $globalOption;
        $this->config     = $globalOption->getBusinessConfig();
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('startMeterReading','text', array('required'=> true,'attr'=>array('class'=>'m-wrap span8 number','placeholder'=>'Start Meter Reading','autocomplete'=>'off')))
            ->add('businessParticular', 'entity', array(
                'required'    => true,
                'class' => 'Appstore\Bundle\BusinessBundle\Entity\BusinessParticular',
                'empty_value' => '---Choose a item ---',
                'property' => 'name',
                'attr'=>array('class'=>'span12 m-wrap number'),
                'constraints' =>array( new NotBlank(array('message'=>'Please select item name')) ),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where("e.status = 1")
                        ->andWhere("e.businessConfig =".$this->config->getId());
                },
            ))
            ->add('wearhouse', 'entity', array(
                'required'    => true,
                'class' => 'Appstore\Bundle\BusinessBundle\Entity\WearHouse',
                'empty_value' => '---Choose a from wearhouse ---',
                'property' => 'name',
                'attr'=>array('class'=>'span12 m-wrap'),
                'constraints' =>array( new NotBlank(array('message'=>'Please select your vendor name')) ),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where("e.status = 1")
                        ->andWhere("e.businessConfig =".$this->config->getId());
                },
            ))
            ->add('salesBy', 'entity', array(
                'required'    => true,
                'class' => 'Core\UserBundle\Entity\User',
                'property' => 'userFullName',
                'attr'=>array('class'=>'span12 m-wrap'),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('u')
                        ->where("u.isDelete != 1")
	                    ->andWhere("u.enabled = 1")
	                    ->andWhere("u.domainOwner = 2")
	                    ->andWhere("u.globalOption =".$this->globalOption->getId())
                        ->orderBy("u.username", "ASC");
                }
            ));
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Appstore\Bundle\BusinessBundle\Entity\BusinessBatchParticular'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'businessInvoice';
    }

}
