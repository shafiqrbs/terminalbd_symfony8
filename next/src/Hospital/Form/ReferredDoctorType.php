<?php

namespace App\Hospital\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Hospital\Entity\Particular;
use App\Hospital\Repository\HmsCategoryRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ReferredDoctorType extends AbstractType
{

    public function __construct(private readonly HmsCategoryRepository $emCategory, private readonly GlobalOption $globalOption, private readonly LocationRepository $location)
    {
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter Doctor/Reference name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter doctor/reference name')
                    ]]
            )
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','autocomplete'=>'off','placeholder'=>'Mobile no']])
            ->add('isDoctor')
            ->add('particularCode',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter referred code']])
            ->add('commission',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','autocomplete'=>'off','placeholder'=>'Commission no']])
            ->add('doctorSignature',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Doctor signature information']])
            ->add('address',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 ','rows' => 4,'placeholder'=>'Enter customer address']])
            ->add('location', EntityType::class, [
                'required'    => false,
                'empty_value' => '---Select Location---',
                'attr'=>['class'=>'select2 span12'],
                'class' => Location::class,
                'choices'=> $this->LocationChoiceList(),
                'choices_as_values' => true,
                'choice_label' => 'nestedLabel',
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Particular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_hospitalbundle_referred_doctor';
    }

    protected function LocationChoiceList()
    {
        return $this->location->getLocationOptionGroup();

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
