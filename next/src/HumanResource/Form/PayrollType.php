<?php

namespace App\HumanResource\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\HumanResource\Entity\Payroll;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayrollType extends AbstractType
{

    /** @var  GlobalOption */
    private $option;

    public function __construct( GlobalOption $option)
    {
        $this->global = $option;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder

            ->add('effectedMonth',TextType::class, ['attr'=>['class'=>'m-wrap span8 datePickerMonth','placeholder'=>'Enter Effected Month','autocomplete'=>'off'],
                'constraints' =>[
                    new NotBlank(message: 'Please input effected month'),
                ]
            ])
            ->add('bonusApplicable')
            ->add('arearApplicable')
            ->add('salaryType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span8'],
                'expanded'      =>false,
                'multiple'      =>false,
                 'constraints' =>[
                     new NotBlank(message: 'Please Choose Salary Type'),
                 ],
                'empty_value' => '-Choose Salary Type-',
                'choices' => [
                    'fixed' => 'Fixed',
                    'honourable' => 'Honourable',
                    'day' => 'Day',
                    'hour' => 'Hour',
                ],
            ])
            /*->add('employeeType', 'choice', array(
                'attr'=>array('class'=>'span8 m-wrap'),
                'constraints' =>array(
                    new NotBlank(array('message'=>'Select Employee Type'))
                ),
                'empty_value' => '---Select Employee Type---',
                'choices' => array(
                    'Full Time' => 'Full Time',
                    'Half Time' => 'Half Time',
                    'Contractual' => 'Contractual',
                    'Hourly' => 'Hourly'
                ),
            ))*/
            ->add('remark',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter any remark']])

        ;


    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payroll::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'employee';
    }
}
