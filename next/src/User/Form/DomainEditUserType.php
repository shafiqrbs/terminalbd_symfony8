<?php

namespace App\User\Form;

use App\Form\ChoiceList;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

class DomainEditUserType extends AbstractType
{
    protected $user;


    /** @var  GlobalOption */
    private $globalOption;

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
        $this->designation = $options['designation'];

        $builder

            ->add('email', TextType::class, array('attr'=>array('class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter your valid email address'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please enter your email address')),
                        new Length(array('max'=>200))
                    ))
            )
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
            ->add('enabled');
            $builder->add('profile', DomainUserProfileType::class, ['globalOption' => $this->globalOption, 'designation' => $this->designation]);

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
            'globalOption' => null,
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

    public function getAccessRoleGroup(){

        return $userGroups =$this->user->getAccessRoleGroup($this->globalOption);
    }
    public function getAppRoleGroup(){
        return $userGroups = $this->user->getAndroidRoleGroup();
    }
}
