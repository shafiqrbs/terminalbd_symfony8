<?php

namespace App\User\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Doctrine\ORM\EntityRepository;

use App\User\Form\Type\ProfileFormType;
use App\Location\Repository\LocationRepository;
use App\Tool\Form\GlobalOptionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
       // var_dump($options['data']->getId());
        $userID = (!empty($options['data'])) ? $options['data']->getId():0;
        $builder
        ->add('name', TextType::class, array('attr'=>array('class'=>'m-wrap placeholder-no-fix','placeholder'=>'Enter your full name'),
        'constraints' =>array(
            new NotBlank(array('message'=>'Please input required')),
            new Length(array('max'=>200))
        )))
        ->add('email', EmailType::class, array('attr'=>array('label' => 'form.email', 'translation_domain' => 'FOSUserBundle' , 'class'=>'m-wrap placeholder-no-fix','placeholder'=>'Enter your email address' , 'autocomplete' => 'off'),
                                        'constraints' =>array(
                                            new NotBlank(array('message'=>'Please input required')),
                                            new Length(array('max'=>200))
                                        )));

        if($userID == 0){
            $builder->add('username', TextType::class, array('attr'=>array('label' => 'form.username', 'translation_domain' => 'FOSUserBundle' , 'class'=>'m-wrap placeholder-no-fix','placeholder'=>'Enter your user name', 'autocomplete' => 'off'),
                                                   'constraints' =>array(
                                                       new NotBlank(array('message'=>'Please input required')),
                                                       new Length(array('max'=>200))
                                                   )))
                ->add('plainPassword', RepeatedType::class, array(
                    'type' => PasswordType::class,
                    'options' => array('translation_domain' => 'FOSUserBundle'),
                    'first_options' => array('label' => 'form.password'),
                    'second_options' => array('label' => 'form.password_confirmation'),
                    'invalid_message' => 'fos_user.password.mismatch'
                ));
            $builder->add('globalOption', GlobalOptionType::class);
        }

	    $builder->add('profile', ProfileFormType::class, ['edit' => $userID > 0]);


        if($userID > 0) {

            $builder->add('groups', EntityType::class, array(
                'class' => 'App\User\Entity\Group',
                'query_builder' => function(EntityRepository $er) {
                    return $er->createQueryBuilder('g')
                        ->andWhere("g.name != :group")
                        ->setParameter('group', 'Super Administrator');
                },
                'attr'     =>array('id' => 'select2_sample4' , 'class' => 'span12 select2'),
                'choice_label' => 'name',
                'multiple' => true,
            ));
        }

    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
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
