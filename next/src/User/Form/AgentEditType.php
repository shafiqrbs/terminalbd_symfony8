<?php

namespace App\User\Form;

use App\Form\ChoiceList;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AgentEditType extends AbstractType
{
    protected $location;


    /** @var  GlobalOption */
    private $globalOption;

    /** @var  LocationRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];

        $builder

            ->add('roles', ChoiceType::class, array(
                'attr'=>array('class'=>'m-wrap span12  check-list'),
                'required'=>true,
                'constraints' =>array(
                    new NotBlank(array('message'=>'Please input required'))
                ),
                'multiple'    => true,
                'expanded'  => true,
                'empty_data'  => null,
                'choices'   => ChoiceList::flip($this->getAccessRoleGroup()))
            )

            ->add('username', TextType::class, array('attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off' ,'placeholder'=>'Enter your user name'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please enter your user name')),
                        new Length(array('max'=>200))
                    ))
            )
            ->add('enabled')
            ->add('email', TextType::class, array('attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter your valid email address'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please enter user email address')),
                        new Length(array('max'=>200))
            ))
            );
            $builder->add('profile', AgentProfileType::class, ['location' => $this->globalOption]);

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'location' => null,
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

    public function getAccessRoleGroup(){

        $array = array();

        $array['Agent'] = array(
            'ROLE_AGENT' => 'System Agent',
            'ROLE_CUSTOMER' => 'Domain Customer',
        );

        return $array;

    }
}
