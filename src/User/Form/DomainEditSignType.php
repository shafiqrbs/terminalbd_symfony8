<?php

namespace App\User\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use App\Tool\Repository\DesignationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DomainEditSignType extends AbstractType
{
    protected $designation;


    /** @var  GlobalOption */
    private $globalOption;

    /** @var  LocationRepository */
    private $location;

    /** @var  DesignationRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];
        $this->designation = $options['designation'];

        $builder
            ->add('email', TextType::class, array('attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter your valid email address'),
                    'constraints' =>array(
                        new NotBlank(message: 'Please enter your email address'),
                        new Length(max: 200)
                    ))
            );
            $builder->add('profile', MyProfileType::class, ['globalOption' => $this->globalOption, 'location' => $this->location, 'designation' => $this->designation]);

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'location' => null,
            'designation' => null,
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
        return 'Core_userbundle_user';
    }
}
