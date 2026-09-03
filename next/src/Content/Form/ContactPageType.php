<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Content\Entity\ContactPage;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactPageType extends AbstractType
{

    public function __construct(private readonly LocationRepository $location)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('address1',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter address'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('location', EntityType::class, [
                'required'    => false,
                'empty_value' => '---Select Location---',
                'attr'=>['class'=>'select2 span12'],
                'class' => Location::class,
                'choices'=> $this->LocationChoiceList(),
                'choices_as_values' => true,
                'choice_label' => 'nestedLabel',
            ])
            ->add('contactMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter contact mobile']])
            ->add('contactEmail',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter contact email']])
            ->add('latitude',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter latitude']])
            ->add('longitude',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter longitude']])
            ->add('address2',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=>3,'placeholder'=>'Enter additional information ']])
            ->add('postalCode',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter postal code']])
            ->add('fax',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter fax no']])
            ->add('additionalPhone',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter additional phone/mobile no']])
            ->add('additionalEmail',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter additional email']])
            ->add('contactPerson',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter contact person name']])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter designation']])
            ->add('startHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10', 'data-format' => 'hh:mm A','placeholder'=>'Start hour']])
            ->add('endHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10', 'data-format' => 'hh:mm A', 'placeholder'=>'End hour']])
            ->add('weeklyOffDay', ChoiceType::class, [
                'attr'=>['class'=>'check-list span12'],
                'expanded'      =>true,
                'multiple'      =>true,
                'choices' => ['Sunday' => 'Sunday',  'Monday' => 'Monday', 'Tuesday' => 'Tuesday', 'Wednesday' => 'Wednesday', 'Thursday' => 'Thursday', 'Friday' => 'Friday', 'Saturday' => 'Saturday'],
            ])
            ->add('isContactForm')
            ->add('isBranch')
            ->add('isBaseInformation')

        ;

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactPage::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_contactpage';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }

}
