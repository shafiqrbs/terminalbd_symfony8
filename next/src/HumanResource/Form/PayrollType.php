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
    protected $global;


    /** @var  GlobalOption */
    private $option;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->global = $options['option'];


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
                'placeholder' => '-Choose Salary Type-',
                'choices' => ['Fixed' => 'fixed', 'Honourable' => 'honourable', 'Day' => 'day', 'Hour' => 'hour'],
            ])
            /*->add('employeeType', ChoiceType::class, array(
                'attr'=>array('class'=>'span8 m-wrap'),
                'constraints' =>array(
                    new NotBlank(array('message'=>'Select Employee Type'))
                ),
                'placeholder' => '---Select Employee Type---',
                'choices' => array('Full Time' => 'Full Time', 'Half Time' => 'Half Time', 'Contractual' => 'Contractual', 'Hourly' => 'Hourly'),
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
            'option' => null,
        ]);

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
