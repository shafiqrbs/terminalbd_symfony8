<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DomainUser\Entity\Customer;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerForDmsType extends AbstractType
{
    protected $location;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->location = $options['location'];

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs patientName','autocomplete'=>'off','placeholder'=>'Enter patient name'],
                'constraints' =>[
                    new NotBlank(message: 'Enter patient name'),
                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs mobile','autocomplete'=>'off','placeholder'=>'Enter mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Enter patient mobile no'),
                ]
            ])
            ->add('age',NumberType::class, ['attr'=>['class'=>'m-wrap span12 numeric inputs patientAge','placeholder'=>'Enter age'],
                'constraints' =>[
                    new NotBlank(message: 'Patient age'),
            ]])
            ->add('weight',NumberType::class, ['attr'=>['class'=>'m-wrap span12 numeric inputs patientAge','placeholder'=>'Enter weight']])
             ->add('ageType', ChoiceType::class, [
                'attr'=>['class'=>'span12 select-custom inputs ageType'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Years' => 'Years', 'Months' => 'Months', 'Day' => 'Day']
            ])
            ->add('gender', ChoiceType::class, [
                'attr'=>['class'=>'span12 select-custom inputs gender'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Female' => 'Female', 'Male' => 'Male', 'Others' => 'Others'],
            ])
            ->add('ageGroup', ChoiceType::class, [
                'attr'=>['class'=>'span12 select-custom inputs'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Adult' => 'Adult', 'Child' => 'Child'],
            ])
            ->add('address',TextareaType::class, ['attr'=>['required'=>false ,'class'=>'m-wrap span12 resize inputs address','rows'=> 3,'autocomplete'=>'off','placeholder'=>'Enter patient address']
            ])
            ->add('location', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select Location---',
                'attr'=>['class'=>'select2 span12 location'],
                'class' => Location::class,
                'choices'=> $this->LocationChoiceList(),
                'choice_label' => 'nestedLabel',
            ])

        ;
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'location' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Customer::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_domainuserbundle_customer';
    }

    protected function LocationChoiceList()
    {
        return $this->location->getLocationOptionGroup();

    }

}
