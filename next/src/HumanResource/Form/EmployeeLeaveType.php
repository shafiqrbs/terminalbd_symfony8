<?php

namespace App\HumanResource\Form;


use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\HumanResource\Entity\LeaveSetup;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\HumanResource\Entity\EmployeeLeave;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmployeeLeaveType extends AbstractType
{


    public function __construct(private readonly GlobalOption $globalOption)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('leaveSetup', EntityType::class, [
                'required'    => false,
                'class' => LeaveSetup::class,
                'property' => 'leaveOffDay',
                'attr'=>['class'=>'span12'],
                'empty_value' => '---Select Leave Type---',
                'constraints' =>[
                    new NotBlank(message: 'Select leave type required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption ={$this->globalOption->getId()}")
                    ->orderBy("e.name", "ASC")
            ])

            ->add('startDate',TextType::class, ['attr'=>['class'=>'m-wrap span12 date-picker','placeholder'=>'Enter start date'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
            ->add('endDate',TextType::class, ['attr'=>['class'=>'m-wrap span12 date-picker','placeholder'=>'Enter end date'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                ]
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployeeLeave::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hr_leave';
    }


}
