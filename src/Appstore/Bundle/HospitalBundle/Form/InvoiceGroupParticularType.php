<?php

namespace Appstore\Bundle\HospitalBundle\Form;


use Appstore\Bundle\HospitalBundle\Entity\HospitalConfig;
use Appstore\Bundle\HospitalBundle\Entity\Particular;
use Appstore\Bundle\HospitalBundle\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class InvoiceGroupParticularType extends AbstractType
{

    /** @var  HospitalConfig */
    private $hospitalConfig;

    /** @var  HmsCategoryRepository */
    private $emCategory;

    function __construct(HospitalConfig $hospitalConfig,HmsCategoryRepository $emCategory)
    {
        $this->hospitalConfig  = $hospitalConfig;
        $this->emCategory = $emCategory;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('assignDoctor', 'entity', array(
                'required'    => false,
                'class' => 'Appstore\Bundle\HospitalBundle\Entity\Particular',
                'property' => 'reportDoctor',
                'empty_value' => '---Select Assign Doctor---',
                'attr'=>array('class'=>'span12 select2'),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where('e.hospitalConfig ='.$this->hospitalConfig->getId())
                        ->andWhere("e.service = 5")
                        ->andWhere("e.status = 1")
                        ->orderBy("e.name","ASC");
                }
            ))
            ->add('assignLabuser', 'entity', array(
                'required'    => false,
                'class' => 'Appstore\Bundle\HospitalBundle\Entity\Particular',
                'property' => 'reportDoctor',
                'empty_value' => '---Select Assign Lab User---',
                'attr'=>array('class'=>'span12 select2'),
                'query_builder' => function(EntityRepository $er){
                    return $er->createQueryBuilder('e')
                        ->where('e.hospitalConfig ='.$this->hospitalConfig->getId())
                        ->andWhere("e.service = 9")
                        ->andWhere("e.status = 1")
                        ->orderBy("e.name","ASC");
                }
            ))
            ->add('process', 'choice', array(
                'attr'=>array('class'=>'span12 select-custom'),
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => array('Done' => 'Done', 'In-progress' => 'In-progress','Damage' => 'Damage', 'Impossible' => 'Impossible'),
            ))
            ->add('comment','textarea', array('attr'=>array('class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Add any comment','autocomplete'=>'off')))
            ->add('category', 'entity', array(
                'required'    => true,
                'empty_value' => '---Select pathology---',
                'attr'=>array('class'=>'m-wrap span12 select2'),
                'class' => 'Appstore\Bundle\HospitalBundle\Entity\HmsCategory',
                'property' => 'nestedLabel',
                'choices'=> $this->PathologyChoiceList()
            ))
        ;

    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Appstore\Bundle\HospitalBundle\Entity\InvoicePathologicalGroup'
        ));
    }

    /**
     * @return mixed
     */
    protected function PathologyChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 2 /** Pathology */ );

    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'invoiceParticular';
    }

}
