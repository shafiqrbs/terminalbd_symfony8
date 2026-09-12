<?php

namespace App\User\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use App\User\Form\Type\ProfileType;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerRegisterType extends AbstractType
{
    protected $location;


    /** @var  LocationRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->location = $options['location'];

            $builder->add('email', TextType::class, array('attr'=>array('class'=>'span12 form-control','placeholder'=>'Enter your secured email address')));
            $builder->add('profile', ProfileType::class, ['location' => $this->location]);
           /* ->add('enabled');*/
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
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
}
