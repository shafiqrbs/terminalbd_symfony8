<?php

namespace App\HumanResource\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\User\Entity\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use App\Tool\Repository\DesignationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class EditUserType extends AbstractType
{
    protected $designationRepository;


    /** @var  UserRepository */
    private $user;

    /** @var  GlobalOption */
    private $option;

    /** @var  LocationRepository */
    private $location;

     /** @var  DesignationRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->user = $options['user'];
        $this->option = $options['option'];
        $this->location = $options['location'];
        $this->designationRepository = $options['designationRepository'];

        $builder->add('userGroup', ChoiceType::class, array(
        'attr'=>array('class'=>'m-wrap span8'),
        'expanded'      => false,
        'multiple'      => false,
        'choices' => array('Employee' => 'employee', 'User' => 'user', 'Marketing Executive' => 'marketing'),
        ));
        $builder->add('profile', EmployeeProfileType::class, ['user' => $this->user, 'option' => $this->option, 'location' => $this->location]);
      //  $builder->add('employeePayroll', EmployeePayrollType::class, ['user' => $this->user, 'option' => $this->option, 'location' => $this->location]);
    }

    public function getAccessRoleGroup(){

        return $userGroups = $this->user->getAccessRoleGroup($this->option);
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
            'option' => null,
            'location' => null,
            'designationRepository' => null,
        ]);

        $resolver->setDefaults(array(
            'data_class' => 'App\User\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'user';
    }

}
