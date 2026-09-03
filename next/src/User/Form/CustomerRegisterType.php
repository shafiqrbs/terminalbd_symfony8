<?php

namespace App\User\Form;


use App\User\Form\Type\ProfileType;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class CustomerRegisterType extends AbstractType
{


    /** @var  LocationRepository */
    private $location;


    function __construct( LocationRepository $location)
    {
        $this->location = $location;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
            $builder->add('email','text', array('attr'=>array('class'=>'span12 form-control','placeholder'=>'Enter your secured email address')));
            // TODO(migration): SF3+ takes a form type class-string, not an instance.
            // Move these constructor arguments into ProfileType::configureOptions() and pass them as form options: $this->location
            $builder->add('profile', new ProfileType($this->location));
           /* ->add('enabled');*/
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
