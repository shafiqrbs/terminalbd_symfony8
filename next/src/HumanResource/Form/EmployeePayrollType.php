<?php

namespace App\HumanResource\Form;


use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Bank;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\HumanResource\Entity\EmployeePayroll;
use App\User\Entity\Repository\UserRepository;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EmployeePayrollType extends AbstractType
{

    /** @var  GlobalOption */
    private $option;

    public function __construct( private readonly UserRepository $user , GlobalOption $option , private readonly LocationRepository $location)
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

            ->add('effectedMonth',TextType::class, ['attr'=>['class'=>'m-wrap span8 datePickerMonth','placeholder'=>'Enter Effected Month'],
                'constraints' =>[
                    new NotBlank(message: 'Please input effected month'),
                ]
            ])
            ->add('basicAmount',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter basic amount'],
                'constraints' =>[
                    new NotBlank(message: 'Please input basic amount'),
                ]
            ])
            ->add('bonusApplicable')
            ->add('accountNo',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter Account No']])
            ->add('bankAccountName',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter Bank Account Name']])
            ->add('branch',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Bank branch name']])
            ->add('taxNumber',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Tax Number']])

            ->add('bonusPercentage',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter bonus percentage']])
            ->add('bank', EntityType::class, [
                'required'    => false,
                'class' => Bank::class,
                'property' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'empty_value' => '---Select Bank Name---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->orderBy("e.name", "ASC")
            ])
            ->add('remark',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter any remark']])
            ->add('mobileAccount',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter Mobile  Account']])
            ->add('mobileBanking', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span8'],
                'expanded'      =>false,
                'multiple'      =>false,
                'empty_value' => '---Select Mobile Banking---',
                'choices' => [
                    'Bkash' => 'Bkash',
                    'Rocket' => 'Rocket',
                    'Nagod' => 'Nagod',
                ],
            ])
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
            ->add('paymentMethod', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span8'],
                'expanded'      =>false,
                'multiple'      =>false,
                'constraints' =>[
                    new NotBlank(message: 'Please Choose Payment Method'),
                ],
                'empty_value' => '---Choose Payment Method---',
                'choices' => [
                    'Cash' => 'Cash in Hand',
                    'Bank' => 'Bank',
                    'Mobile Banking' => 'Mobile Banking',

                ],
            ]);


    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EmployeePayroll::class
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
