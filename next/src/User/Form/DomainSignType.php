<?php

namespace App\User\Form;

use App\Form\ChoiceList;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\User\Entity\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use App\Tool\Repository\DesignationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DomainSignType extends AbstractType
{
    protected $user;


    /** @var  GlobalOption */
    private $globalOption;

    /** @var  LocationRepository */
    private $location;

    /** @var  DesignationRepository */
    private $designation;

    /** @var  UserRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->user = $options['user'];
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];
        $this->designation = $options['designation'];

        $builder

            ->add('roles', ChoiceType::class, array(
                    'attr'=>array('class'=>'category form-control'),
                    'required'=>true,
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please input required'))
                    ),
                    'multiple'    => true,
                    'empty_data'  => null,
                    'choices'   => ChoiceList::flip($this->getAccessRoleGroup()))
            )

            ->add('appRoles', ChoiceType::class, array(
                    'attr'=>array('class'=>'category form-control'),
                    'required'=>false,
                    'multiple'    => true,
                    'empty_data'  => null,
                    'choices'   => ChoiceList::flip($this->getAppRoleGroup()))
            )

            ->add('username', TextType::class, array('attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off' ,'placeholder'=>'Enter your user name'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please enter your user name')),
                        new Length(array('max'=>200))
                    ))
            )
            ->add('enabled')
            ->add('plainPassword', RepeatedType::class, array(
                'attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off'),
                'type' => PasswordType::class,
                'constraints' =>array(
                    new NotBlank(array('message'=>'Please enter user password'))),
                'options' => array('translation_domain' => 'FOSUserBundle'),
                'first_options' => array('label' => 'form.password'),
                'second_options' => array('label' => 'form.password_confirmation'),
                'invalid_message' => 'fos_user.password.mismatch',
            ))
            ->add('email', TextType::class, array('attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter your valid email address'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please enter user email address')),
                        new Length(array('max'=>200))
            ))
            );
            $builder->add('profile', DomainProfileType::class, ['globalOption' => $this->globalOption, 'location' => $this->location, 'designation' => $this->designation]);

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
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
        return 'user';
    }

    public function getAccessRoleGroup(){

        return $userGroups = $this->user->getAccessRoleGroup($this->globalOption);
    }
    public function getAppRoleGroup(){

        return $userGroups = $this->user->getAndroidRoleGroup();
    }
}
