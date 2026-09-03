<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Content\Entity\Page;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class  BranchType extends AbstractType
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

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter location name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Enter mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]]
            )
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter email address']

            ])
            ->add('phone',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter phone/mobile no']

            ])
            ->add('fax',TextType::class, ['attr'=>['class'=>'m-wrap span12 phone','placeholder'=>'Enter fax no']

            ])
            ->add('contactPerson',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter contact person']
            ])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter designation']
            ])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter address'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]])
            ->add('latitude',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter latitude']
            ])
            ->add('longitude',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter longitude']
            ])
            ->add('location', EntityType::class, [
                'required'    => false,
                'empty_value' => '---Select Location---',
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ],
                'attr'=>['class'=>'select2 span12'],
                'class' => Location::class,
                'choices'=> $this->LocationChoiceList(),
                'choices_as_values' => true,
                'choice_label' => 'nestedLabel',
            ])
            ->add('status')
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_page';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}
