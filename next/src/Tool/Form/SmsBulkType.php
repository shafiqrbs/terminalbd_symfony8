<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\SmsBulk;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SmsBulkType extends AbstractType
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
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter module name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])

            ->add('smsText',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Create sending sms text'],
                'constraints' =>[
                    new NotBlank(message: 'Create sending sms text'),
                    new Length(max: 200)
                ]
            ])

            ->add('bulkMobile',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows'=> '6','placeholder'=>'Bulk mobile no']])

            ->add('location', EntityType::class, [
                'required'    => false,
                'empty_value' => '---Select Location---',
                'attr'=>['class'=>'select2 m-wrap span12'],
                'class' => Location::class,
                'choices'=> $this->LocationChoiceList(),
                'choices_as_values' => true,
                'choice_label' => 'nestedLabel',
            ])

            ->add('sourceTo', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'empty_value' => '---Select Source---',
                'required'    => false,
                'choices' => [
                    'Customer' => 'Customer',
                    'Member' => 'Member',
                ],
            ]);
            /*->add('file','file',array('attr'=>array('class'=>'default span12')))*/

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SmsBulk::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_bulksms';
    }


    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}
