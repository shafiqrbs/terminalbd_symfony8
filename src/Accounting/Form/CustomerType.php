<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\Profile;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerType extends AbstractType
{
    protected $location;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->location = $options['location'];

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Customer name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter customer name')
                    ]]
            )
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','autocomplete'=>'off','placeholder'=>'Mobile no'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter mobile no')
                    ]]
            )
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Email address']])
            ->add('address',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 ','rows'=>4,'placeholder'=>'Enter customer address']])
            ->add('location', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select Location---',
                'attr'=>['class'=>'select2 m-wrap span12'],
                'class' => Location::class,
                'constraints' =>[
                    new NotBlank(message: 'Select customer location')
                ],
                'choices'=> $this->LocationChoiceList(),
                'choice_label' => 'nestedLabel',
            ])

            ->add('userGroup', ChoiceType::class, [
                'attr'=>['class'=>'span12 m-wrap'],
                'expanded'      =>false,
                'required'    => true,
                'multiple'      =>false,
                'choices' => ['Employee' => 'employee', 'Stakeholder' => 'stakeholder', 'Other' => 'other'],
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'location' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Profile::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'profile';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}
