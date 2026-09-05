<?php

namespace App\DoctorPrescription\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\User\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DoctorPrescription\Entity\DpsParticular;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DoctorType extends AbstractType
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

            ->add('room',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter room/cabin name or no']])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter fees']])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter doctor designation']])
            ->add('phoneNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter additional phone no']])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter valid email']])
            ->add('doctorPrescription',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>6,'placeholder'=>'Enter specialist']])
            ->add('startHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10', 'data-format' => 'hh:mm A','placeholder'=>'Start hour']])
            ->add('endHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10', 'data-format' => 'hh:mm A', 'placeholder'=>'End hour']])
            ->add('file')
            ->add('weeklyOffDay', ChoiceType::class, [
                'attr'=>['class'=>'check-list span12'],
                'expanded'      =>true,
                'multiple'      =>true,
                'choices' => ['Sunday' => 'Sunday', 'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday', 'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday'],
            ])
            ->add('room',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter room/cabin name or no']])
            ->add('assignDoctor', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select doctor from employee list---',
                'class' => User::class,
                'choice_label' => 'userDoctor',
                'constraints' =>[
                    new NotBlank(message: 'Please select doctor from employee list'),
                ],
                'attr'=>['class'=>'span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join('e.profile','p')
                    ->where("e.enabled = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->andWhere('e.roles LIKE :roles')
                    ->setParameter('roles', '%"ROLE_DPS_DOCTOR"%')
                    ->orderBy("p.name","ASC")
            ]);
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
            'data_class' => DpsParticular::class,
            'error_bubbling'     => false,
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_particular';
    }

}
