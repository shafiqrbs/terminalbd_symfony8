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
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('leaveSetup', EntityType::class, [
                'required'    => false,
                'class' => LeaveSetup::class,
                'choice_label' => 'leaveOffDay',
                'attr'=>['class'=>'span12'],
                'placeholder' => '---Select Leave Type---',
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
            'globalOption' => null,
        ]);

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
