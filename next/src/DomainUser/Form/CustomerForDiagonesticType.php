<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DomainUser\Entity\Customer;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerForDiagonesticType extends AbstractType
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

            ->add('customerId',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs patientName','autocomplete'=>'off','placeholder'=>'Enter patient ID'],
                'constraints' =>[
                    new NotBlank(message: 'Enter patient ID'),
                ]
            ])
             ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 inputs patientName','autocomplete'=>'off','placeholder'=>'Enter patient name'],
                'constraints' =>[
                    new NotBlank(message: 'Enter patient name'),
                ]
            ])
            ->add('mobile',NumberType::class, ['attr'=>['class'=>'m-wrap span12  inputs','autocomplete'=>'off','placeholder'=>'Enter valid mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Enter valid mobile no'),
                ]
            ])
            ->add('age',NumberType::class, ['attr'=>['class'=>'m-wrap span12 numeric inputs patientAge','placeholder'=>'Enter valid age'],
                'constraints' =>[
                    new NotBlank(message: 'Patient age'),
            ]])

            ->add('ageType', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap inputs ageType'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Years' => 'Years','Months' => 'Months','Day' => 'Day']
            ])
            ->add('gender', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap inputs gender'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => ['Female' => 'Female','Male' => 'Male', 'Others' => 'Others'],
            ])
            ->add('location', EntityType::class, [
                'required'    => false,
                'empty_value' => '---Select Location---',
                'attr'=>['class'=>'select2 m-wrap span12'],
                'class' => Location::class,
                'choices'=> $this->LocationChoiceList(),
                'choices_as_values' => true,
                'choice_label' => 'nestedLabel',
            ])
            ->add('address',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 resize inputs address','rows'=>3,'autocomplete'=>'off','placeholder'=>'Enter patient address']
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class
        ]);
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'customer';
    }

}
