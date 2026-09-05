<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\User\Entity\User;
use App\Hospital\Entity\HmsCategory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use App\Hospital\Repository\HmsCategoryRepository;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class DoctorType extends AbstractType
{
    protected $emCategory;
    protected $globalOption;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->emCategory = $options['emCategory'];
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter Doctor name'],
                    'constraints' =>[new NotBlank(message: 'Please enter doctor name')]]
            )
            ->add('room',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter room/cabin name or no']])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter fees']])
            ->add('ipdVisitCharge',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter ipd visit charge']])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter doctor designation']])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter additional phone no']])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter valid email']])
            ->add('currentJob',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter current job']])
            ->add('specialist',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Enter specialist']])
            ->add('doctorSignature',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>4,'autocomplete'=>'off','placeholder'=>'Enter Doctor signature'],
                    'constraints' =>[new NotBlank(message: 'Please enter doctor signature')]]
            )
            ->add('doctorSignatureBangla',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>4,'autocomplete'=>'off','placeholder'=>'Enter Doctor signature']])
            ->add('educationalDegree',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Enter educational degree']])
            ->add('visitTime',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Enter visit time duration']])
            ->add('startHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10', 'data-format' => 'hh:mm A','placeholder'=>'Start hour']])
            ->add('endHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10', 'data-format' => 'hh:mm A', 'placeholder'=>'End hour']])
            ->add('weeklyOffDay', ChoiceType::class, [
                'attr'=>['class'=>'check-list span12'],
                'expanded'      =>true,
                'multiple'      =>true,
                'choices' => ['Sunday' => 'Sunday', 'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday', 'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday'],
            ])
            ->add('room',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter room/cabin name or no']])
            ->add('particularCode',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter particular code']])
            ->add('file')
            ->add('signatureFile')
            ->add('sendToAccount')
            ->add('assignDoctor', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select doctor from employee list---',
                'class' => User::class,
                'choice_label' => 'userDoctor',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join('e.profile','p')
                    ->where("e.enabled = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->andWhere('e.roles LIKE :roles')
                    ->setParameter('roles', '%"ROLE_DOMAIN_HOSPITAL_DOCTOR"%')
                    ->orderBy("p.name","ASC")
            ])
            ->add('assignOperator', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select operator---',
                'class' => User::class,
                'choice_label' => 'userFullName',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.enabled = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->andWhere('e.roles LIKE :roles')
                    ->setParameter('roles', '%"ROLE_DOMAIN_OPERATOR"%')
                    ->orderBy("e.id","ASC")
            ])
            /*->add('category', EntityType::class, array(
                'required'    => true,
                'placeholder' => '---Select category---',
                'attr'=>array('class'=>'m-wrap span12 select2'),
                'class' => 'App\Hospital\Entity\HmsCategory',
                'choice_label' => 'nestedLabel',
                'choices'=> $this->PathologyChoiceList()
            ))*/

            ->add('department', EntityType::class, [
                'required'    => true,
                'placeholder' => '---Select department---',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'class' => HmsCategory::class,
                'choice_label' => 'nestedLabel',
                'choices'=> $this->DepartmentChoiceList()
            ])
        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'emCategory' => null,
            'globalOption' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Particular::class,
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

    /**
     * @return mixed
     */
    protected function PathologyChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 2 /** Pathology */ );

    }
    /**
     * @return mixed
     */
    protected function DepartmentChoiceList()
    {
        return $this->emCategory->getParentCategoryTree($parent = 7 /** Department */);

    }

}
