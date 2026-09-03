<?php

namespace App\User\Form;


use App\User\Entity\User;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use App\Tool\Repository\DesignationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class DomainSignType extends AbstractType
{

    /** @var  User */
    private $user;

    /** @var  GlobalOption */
    private $globalOption;

    /** @var  LocationRepository */
    private $location;

    /** @var  DesignationRepository */
    private $designation;


    function __construct(User $user , GlobalOption $globalOption, LocationRepository $location, DesignationRepository $designation)
    {
        $this->user = $user;
        $this->globalOption = $globalOption;
        $this->location = $location;
        $this->designation = $designation;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('roles', 'choice', array(
                'attr'=>array('class'=>'m-wrap span12 check-list'),
                'required'=>true,
                'constraints' =>array(
                    new NotBlank(array('message'=>'Please input required'))
                ),
                'multiple'    => true,
                'expanded'  => true,
                'empty_data'  => null,
                'choices' => array('ROLE_DOMAIN_USER' => 'User','ROLE_DOMAIN_INVENTORY_SALES' => 'Inventory Sales/Delivery','ROLE_DOMAIN_INVENTORY_PURCHASE' => 'Inventory Purchase/Receive',  'ROLE_DOMAIN_MANAGER' => 'Domain Manager', 'ROLE_DOMAIN_INVENTORY' => 'Inventory Manager')))

            ->add('username','text', array('attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off' ,'placeholder'=>'Enter your user name'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please enter your user name')),
                        new Length(array('max'=>200))
                    ))
            )
            ->add('enabled')
            ->add('plainPassword', 'repeated', array(
                'attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off'),
                'type' => 'password',
                'options' => array('translation_domain' => 'FOSUserBundle'),
                'first_options' => array('label' => 'form.password'),
                'second_options' => array('label' => 'form.password_confirmation'),
                'invalid_message' => 'fos_user.password.mismatch',
            ))
            ->add('email','text', array('attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter your valid email address'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please enter your secured password')),
                        new Length(array('max'=>200))
                    ))
            );
            // TODO(migration): SF3+ takes a form type class-string, not an instance.
            // Move these constructor arguments into DomainProfileType::configureOptions() and pass them as form options: $this->globalOption , $this->location , $this->designation
            $builder->add('profile', new DomainProfileType($this->globalOption , $this->location , $this->designation));

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
        return 'Core_userbundle_user';
    }
}
