<?php

namespace App\HumanResource\Form;


use App\User\Entity\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use App\Tool\Repository\DesignationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditUserType extends AbstractType
{


    /** @var  UserRepository */
    private $user;

    /** @var  GlobalOption */
    private $option;

    /** @var  LocationRepository */
    private $location;


     /** @var  DesignationRepository */
    private $designationRepository;


    function __construct( UserRepository $user , GlobalOption $option , LocationRepository $location, DesignationRepository $designationRepository)
    {
        $this->location = $location;
        $this->user = $user;
        $this->option = $option;
        $this->designationRepository = $designationRepository;
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('userGroup', 'choice', array(
        'attr'=>array('class'=>'m-wrap span8'),
        'expanded'      => false,
        'multiple'      => false,
        'choices' => array(
            'employee' => 'Employee',
            'user' => 'User',
            'marketing' => 'Marketing Executive',
        ),
        ));
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into \App\DomainUser\Form\EmployeeProfileType::configureOptions() and pass them as form options: $this->user, $this->option,$this->location
        $builder->add('profile', new \App\DomainUser\Form\EmployeeProfileType($this->user, $this->option,$this->location));
      // TODO(migration): SF3+ takes a form type class-string, not an instance.
      // Move these constructor arguments into EmployeePayrollType::configureOptions() and pass them as form options: $this->user, $this->option,$this->location
      //  $builder->add('employeePayroll', new EmployeePayrollType($this->user, $this->option,$this->location));
    }

    public function getAccessRoleGroup(){

        return $userGroups = $this->user->getAccessRoleGroup($this->option);
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\User\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'user';
    }


}
