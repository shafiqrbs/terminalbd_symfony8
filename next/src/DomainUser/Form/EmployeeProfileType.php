<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Designation;
use App\HumanResource\Entity\HrDepartment;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\Profile;
use App\User\Entity\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmployeeProfileType extends AbstractType
{

    public function __construct(private readonly UserRepository $user, private readonly GlobalOption $option, private readonly LocationRepository $location)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter Employee Full Name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please Enter Employee Full Name')
                    ]]
            )
            ->add('fatherName',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Employee Father Name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please Enter Father Name')
                    ]]
            )
            ->add('motherName',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Employee Mother Name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please Enter Mother Name')
                    ]]
            )
            ->add('facebookId',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Employee Facebook ID']]
            )
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','autocomplete'=>'off','placeholder'=>'Enter Mobile no'],
                    'constraints' =>[
                        new NotBlank(message: 'Please Enter Mobile no')
                    ]]
            )
             ->add('phoneNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Phone no'],
                    ]
            )
            ->add('address',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 ','rows'=>2,'placeholder'=>'Enter Employee Address'],
                    'constraints' =>[ new NotBlank(message: 'Please Employee Address')]
                ]
            )
            ->add('permanentAddress',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 ','rows'=>2,'placeholder'=>'Enter Employee Permanent Address']]
            )
            ->add('joiningDate', DateType::class, [
                'widget' => 'choice',
                'years' => range(date('Y'), date('Y')-50),
            ])
            ->add('dob', DateType::class, [
                'widget' => 'choice',
                'years' => range(date('Y'), date('Y')-100),
            ])
            ->add('designation', EntityType::class, [
                'required'    => true,
                'class' => Designation::class,
                'empty_value' => '---Choose a designation---',
                'property' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Select Employee Designation')
                ],
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status =1")
                    ->orderBy("e.name", "ASC"),
            ])
            ->add('nid',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter national id card no']])
            ->add('department', EntityType::class, [
                'required'    => true,
                'class' => HrDepartment::class,
                'empty_value' => '---Choose a department Name---',
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->option->getId())
                    ->andWhere("e.status =1")
                    ->orderBy("e.name", "ASC"),
            ])
            ->add('maritalStatus', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'empty_value' => 'Marital Status',
                'choices' => ['Married' => 'Married','Un-married' => 'Un-married','Single' => 'Single'],
            ])
            ->add('bloodGroup', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'empty_value' => 'Blood Group',
                'choices' => ['A+' => 'A+',  'A-' => 'A-','B+' => 'B+',  'B-' => 'B-',  'O+' => 'O+',  'O-' => 'O-',  'AB+' => 'AB+',  'AB-' => 'AB-'],
            ])
            ->add('religionStatus', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => [
                    'Islam' => 'Islam',
                    'Hinduism' => 'Hinduism',
                    'Buddhism' => 'Buddhism',
                    'Christianity' => 'Christianity',
                    'Other religions' => 'Other religions',
                ],
            ])
            ->add('location', EntityType::class, [
                'required'    => false,
                'empty_value' => '---Select Location---',
                'attr'=>['class'=>'select2 span12'],
                'class' => Location::class,
                'choices'=> $this->LocationChoiceList(),
                'choices_as_values' => true,
                'choice_label' => 'nestedLabel',
            ])
            ->add('file')
            ->add('gender', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'choices' => [
                    'Male' => 'Male',
                    'Female' => 'Female'
                ],
            ])
            ->add('employeeType', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[
                    new NotBlank(message: 'Select Employee Type')
                ],
                'empty_value' => '---Select Employee Type---',
                'choices' => [
                    'Full Time' => 'Full Time',
                    'Half Time' => 'Half Time',
                    'Contractual' => 'Contractual',
                    'Hourly' => 'Hourly',
                ],
            ]);

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class
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
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}
