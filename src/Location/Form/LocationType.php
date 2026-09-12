<?php

namespace App\Location\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Location\Entity\Location;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @property mixed em
 */
class LocationType extends AbstractType
{
    protected $em;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->em = $options['em'];

        $builder

        ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter location name'],
        'constraints' =>[
            new NotBlank(message: 'Please input required'),

        ]
        ])
        ->add('parent', EntityType::class, [
            'required'    => true,
            'placeholder' => '---Select Parent Location---',
            'attr'=>['class'=>'location m-wrap span10 select2'],
            'class' => \App\Location\Entity\Location::class,
            'constraints' =>[
                new NotBlank(message: 'Select Parent Location')
            ],
            'choice_label' => 'nestedLabel',
            'choices'=> $this->LocationChoiceList()
        ]);

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'em' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Location::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_locationbundle_location';
    }

    /**
     * @return mixed
     */
    protected function LocationChoiceList()
    {
        return $locationTree = $this->em->getDistrictOptionGroup();

    }

}
