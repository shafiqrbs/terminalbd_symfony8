<?php

namespace App\User\Form;

use App\User\Form\Type\ProfileType;
use App\User\Form\Type\SignupProfileType;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Form\InitialOptionType;
use App\Tool\Form\SiteSettingAppType;
use App\Tool\Form\SiteSettingType;
use App\Tool\Repository\SyndicateRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class SignupType extends AbstractType
{

    /** @var  SyndicateRepository */
    /** @var  LocationRepository */

    private $em;

    function __construct(SyndicateRepository $em , LocationRepository $location)
    {
        $this->em = $em;
        $this->location = $location;
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('profile', new SignupProfileType());
        // TODO(migration): SF3+ takes a form type class-string, not an instance.
        // Move these constructor arguments into InitialOptionType::configureOptions() and pass them as form options: $this->em,$this->location
        $builder->add('globalOption', new InitialOptionType($this->em,$this->location));
      //  $builder->add('siteSetting', new SiteSettingAppType());
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'App\User\Entity\User',
            'cascade_validation' => true
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'domain';
    }
}
