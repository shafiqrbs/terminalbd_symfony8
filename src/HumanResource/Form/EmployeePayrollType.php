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
    protected $user;
    protected $global;
    protected $location;


    /** @var  GlobalOption */
    private $option;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->user = $options['user'];
        $this->global = $options['option'];
        $this->location = $options['location'];


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
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 m-wrap'],
                'placeholder' => '---Select Bank Name---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->orderBy("e.name", "ASC")
            ])
            ->add('remark',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter any remark']])
            ->add('mobileAccount',TextType::class, ['attr'=>['class'=>'m-wrap span8','placeholder'=>'Enter Mobile  Account']])
            ->add('mobileBanking', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span8'],
                'expanded'      =>false,
                'multiple'      =>false,
                'placeholder' => '---Select Mobile Banking---',
                'choices' => ['Bkash' => 'Bkash', 'Rocket' => 'Rocket', 'Nagod' => 'Nagod'],
            ])
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
            ->add('paymentMethod', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span8'],
                'expanded'      =>false,
                'multiple'      =>false,
                'constraints' =>[
                    new NotBlank(message: 'Please Choose Payment Method'),
                ],
                'placeholder' => '---Choose Payment Method---',
                'choices' => ['Cash in Hand' => 'Cash', 'Bank' => 'Bank', 'Mobile Banking' => 'Mobile Banking'],
            ]);

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
            'option' => null,
            'location' => null,
        ]);

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
