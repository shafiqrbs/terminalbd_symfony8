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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SignupType extends AbstractType
{
    protected $em;
    protected $location;


    /** @var  SyndicateRepository */
    /** @var  LocationRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->em = $options['em'];
        $this->location = $options['location'];

        $builder->add('profile', SignupProfileType::class);
        $builder->add('globalOption', InitialOptionType::class, ['em' => $this->em, 'location' => $this->location]);
      //  $builder->add('siteSetting', SiteSettingAppType::class);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'em' => null,
            'location' => null,
        ]);

        $resolver->setDefaults(array(
            'data_class' => 'App\User\Entity\User',));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'domain';
    }
}
