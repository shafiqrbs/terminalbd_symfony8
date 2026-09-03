<?php

namespace App\User\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\Profile;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class AgentProfileType extends AbstractType
{


    public function __construct(private readonly LocationRepository $location)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your full name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input user full name'),
                    new Length(max: 200)
                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Enter mobile number', 'data-original-title' =>'Must be use personal mobile number.' , 'data-trigger' => 'hover'],
                'constraints' =>[
                    new NotBlank(message: 'Please input user mobile no')
                ]
            ])
            ->add('email',EmailType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your email address', 'data-original-title' =>'Enter your email address' , 'data-trigger' => 'hover'],

            ])
             ->add('facebookId',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your facebook id', 'data-original-title' =>'Enter your facebook id' , 'data-trigger' => 'hover'],

            ])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter address'],
            'constraints' =>[
                    new NotBlank(message: 'Please input address')
            ]
            ])
            ->add('postalCode',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter postal code']])
            ->add('additionalPhone',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter additional phone no']])
            ->add('location', EntityType::class, [
                'required'    => false,
                'empty_value' => '---Select Location---',
                'attr'=>['class'=>'select2 span12'],
                'class' => Location::class,
                'constraints' =>[
                    new NotBlank(message: 'Select user location')
                ],
                'choices'=> $this->LocationChoiceList(),
                'choices_as_values' => true,
                'choice_label' => 'nestedLabel',
            ])
            ->add('file');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'manage_profile';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}