<?php

namespace App\User\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Designation;
use App\DomainUser\Entity\Branches;
use App\Tool\Entity\Bank;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\Profile;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use App\Tool\Repository\DesignationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class DomainUserProfileType extends AbstractType
{
    protected $globalOption;
    protected $designation;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->designation = $options['designation'];

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your full name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input user full name'),
                    new Length(max: 200)
                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Enter mobile number', 'data-original-title' =>'Must be use personal mobile number.' , 'data-trigger' => 'hover'],
             ])
            ->add('joiningDate', DateType::class, [
                'widget' => 'choice',
                'years' => range(date('Y'), date('Y')-50),
            ])

            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter address']])
            ->add('about',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter address']])
            ->add('designation', EntityType::class, [
                'required'    => true,
                'class' => Designation::class,
                'placeholder' => '---Choose a designation---',
                'choice_label' => 'name',
                'constraints' =>[
                    new NotBlank(message: 'Select user designation')
                ],
                'attr'=>['class'=>'m-wrap span1q select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status =1")
                    ->orderBy("e.name", "ASC"),
            ])
            ->add('branches', EntityType::class, [
                'required'    => true,
                'class' => Branches::class,
                'placeholder' => '---Choose a branch---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->orderBy("e.name", "ASC"),
            ])
            ->add('bank', EntityType::class, [
                'required'    => true,
                'class' => Bank::class,
                'placeholder' => '---Choose a bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('branch',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>4 ,'draggable' => 'false' ,'placeholder'=>'Enter your bank branch name']])
            ->add('accountNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your bank account no']])
            ->add('signatureFile')
            ->add('file');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'designation' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Profile::class
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'manage_profile';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();
    }

    protected function DesignationChoiceList()
    {
        return $syndicateTree = $this->designation->getDesignationOptionGroup();

    }

}