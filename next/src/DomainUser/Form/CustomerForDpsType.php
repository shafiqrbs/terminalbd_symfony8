<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DomainUser\Entity\Customer;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerForDpsType extends AbstractType
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

            ->add('age',NumberType::class, ['attr'=>['class'=>'m-wrap span12 numeric inputs patientAge','placeholder'=>'Enter age'],
                'constraints' =>[
                    new NotBlank(message: 'Patient age'),
            ]])

            ->add('weight',NumberType::class, ['attr'=>['class'=>'m-wrap span12 numeric inputs patientAge','placeholder'=>'Enter weight'],
                'constraints' =>[
                    new NotBlank(message: 'Patient weight'),
            ]])
            ->add('bloodPressure',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric inputs patientAge','placeholder'=>'Enter Blood Pressure']])
            ->add('ageType', ChoiceType::class, [
                'attr'=>['class'=>'span12 select-custom inputs ageType'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Years' => 'Years', 'Months' => 'Months', 'Day' => 'Day']
            ])
            ->add('bloodGroup', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12 inputs'],
                'placeholder' => '-Blood Group-',
                'choices' => ['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'O+' => 'O+', 'O-' => 'O-', 'AB+' => 'AB+', 'AB-' => 'AB-'],
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
