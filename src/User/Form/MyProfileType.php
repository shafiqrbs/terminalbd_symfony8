<?php

namespace App\User\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Designation;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Tool\Entity\Bank;
use App\Location\Entity\Location;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\Profile;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use App\Tool\Repository\DesignationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class MyProfileType extends AbstractType
{
    protected $globalOption;
    protected $location;
    protected $designation;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];
        $this->designation = $options['designation'];

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your full name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input user full name'),
                    new Length(max: 200)
                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Enter mobile number', 'data-original-title' =>'Must be use personal mobile number.' , 'data-trigger' => 'hover']])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter address']])
            ->add('permanentAddress',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter permanent address']])
            ->add('designation', EntityType::class, [
                'required'    => true,
                'class' => Designation::class,
                'placeholder' => '---Choose a Designation---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span1q select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status =1")
                    ->orderBy("e.name", "ASC"),
            ])
            ->add('postalCode',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter postal code']])
            ->add('additionalPhone',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your additional phone ']])
            ->add('nid',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter national id card no']])
            ->add('dob',BirthdayType::class, ['attr'=>['class'=>'m-wrap span12']])
            ->add('about',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>'8']])
            ->add('bank', EntityType::class, [
                'required'    => true,
                'class' => Bank::class,
                'placeholder' => '---Choose a Bank---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->orderBy("b.name", "ASC"),
            ])
            ->add('branch',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>4 ,'draggable' => 'false' ,'placeholder'=>'Enter your bank branch name']])
            ->add('accountNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter your bank account no']])
            ->add('location', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select Location---',
                'attr'=>['class'=>'select2 span12'],
                'class' => Location::class,
                'constraints' =>[
                    new NotBlank(message: 'Select User Location')
                ],
                'choices'=> $this->LocationChoiceList(),
                'choice_label' => 'nestedLabel',
            ])
            ->add('bloodGroup', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'choices' => ['A+' => 'A+', 'A-' => 'A-', 'B+' => 'B+', 'B-' => 'B-', 'O+' => 'O+', 'O-' => 'O-', 'AB+' => 'AB+', 'AB-' => 'AB-'],
            ])
            ->add('file');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'location' => null,
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