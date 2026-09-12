<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DomainUser\Entity\Customer;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerForHospitalAdmissionType extends AbstractType
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

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 patientName','autocomplete'=>'off','placeholder'=>'Patient name'],
                'constraints' =>[
                    new NotBlank(message: 'patient name'),
                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span6 select2mobile','autocomplete'=>'off','placeholder'=>'Mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'patient mobile no'),
                ]
            ])
            ->add('fatherName',TextType::class, ['attr'=>['class'=>'m-wrap span6','autocomplete'=>'off','placeholder'=>'Father/spouse name'],

            ])
            ->add('motherName',TextType::class, ['attr'=>['class'=>'m-wrap span6','autocomplete'=>'off','placeholder'=>'Mother name'],

            ])
            ->add('religion',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Religion'],

            ])
            ->add('weight',TextType::class, ['attr'=>['class'=>'m-wrap span3','autocomplete'=>'off','placeholder'=>'Weight']])
            ->add('bloodPressure',TextType::class, ['attr'=>['class'=>'m-wrap span6','autocomplete'=>'off','placeholder'=>'BP']])
            ->add('height',TextType::class, ['attr'=>['class'=>'m-wrap span3','autocomplete'=>'off','placeholder'=>'Height']])
            ->add('diabetes',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Diabetes']])

            ->add('profession',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Profession'],

            ])
            ->add('nationality',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Nationality'],

            ])
            ->add('dob',DateType::class, [
                'years' => range(date('Y'), date('Y')-100),
                'required' => false,
                'attr'=>['class'=>'m-wrap span6 dob','placeholder'=>'Patient date of birth'],
                ])
            ->add('age',NumberType::class, ['attr'=>['class'=>'m-wrap span3 numeric patientAge','placeholder'=>'Age'],
                'constraints' =>[
                    new NotBlank(message: 'Patient age'),
            ]])
            ->add('bloodGroup', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'placeholder' => '--- Select Blood Group ---',
                'choices' => ['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'O+' => 'O+', 'O-' => 'O-', 'AB+' => 'AB+', 'AB-' => 'AB-'],
            ])
            ->add('ageType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span3 select-custom ageType'],
                'expanded'      =>false,
                'multiple'      =>false,
                'constraints' =>[
                    new NotBlank(message: 'patient age type'),
                ],
                'choices' => ['Years' => 'Years', 'Months' => 'Months', 'Day' => 'Day']
            ])
            ->add('gender', ChoiceType::class, [
                'attr'=>['class'=>'span6 m-wrap select-custom gender'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Female' => 'Female', 'Male' => 'Male', 'Others' => 'Others'],
            ])
            ->add('maritalStatus', ChoiceType::class, [
                'attr'=>['class'=>'span6 m-wrap select-custom maritalStatus'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Single' => 'Single', 'Married' => 'Married', 'Divorced' => 'Divorced', 'Widow' => 'Widow'],
            ])

            ->add('alternativeContactPerson',TextType::class, ['attr'=>['class'=>'m-wrap span6 ','placeholder'=>'Guardian name'],
                'constraints' =>[
                    new NotBlank(message: 'Guardian name'),
            ]])

            ->add('alternativeContactMobile',TextType::class, ['attr'=>['class'=>'m-wrap span6 numeric mobile alternativeContactMobile','placeholder'=>'Guardian mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Guardian mobile no'),
                ]])
            ->add('alternativeRelation',TextType::class, ['attr'=>['class'=>'m-wrap span12  alternativeRelation','placeholder'=>'Relationship with the Patient'],
                'constraints' =>[
                    new NotBlank(message: 'Relationship with the Patient'),
            ]])
			->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12 resize','rows'=> 4,'autocomplete'=>'off','placeholder'=>'patient address']
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
        return 'customer';
    }

    protected function LocationChoiceList()
    {
        return $this->location->getLocationOptionGroup();

    }

}
