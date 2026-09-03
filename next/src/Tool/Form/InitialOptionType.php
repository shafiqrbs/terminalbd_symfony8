<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Syndicate;
use App\Tool\Entity\AppModule;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\GlobalOption;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Repository\SyndicateRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class InitialOptionType extends AbstractType
{

    public function __construct(private readonly SyndicateRepository $em, private readonly LocationRepository $location)
    {
    }


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


            $builder

                ->add('name',TextType::class, ['required' => false,'attr'=>['class'=>'m-wrap tooltips span12','placeholder'=>'Enter your business name' , 'data-original-title' =>'Please enter name of organization' , 'data-trigger' => 'hover'],
                    'constraints' =>[
                        new NotBlank(message: 'Please organization name required'),
                        new Length(max: 200)
                    ]
                ])

                ->add('syndicate', EntityType::class, [
                    'constraints' =>[
                        new NotBlank(message: 'Please enter you business type')
                    ],
                    'required'    => true,
                    'attr'=>['class'=>'select2 span12'],
                    'class' => Syndicate::class,
                    'choices'=> $this->SyndicateChoiceList(),
                    'choices_as_values' => true,
                    'choice_label' => 'nestedLabel',
                ])

                ->add('mainApp', EntityType::class, [
                    'required'    => true,
                    'class' => AppModule::class,
                    'empty_value' => '---Select Main Application ---',
                    'property' => 'name',
                    'attr'     =>['id' => '' , 'class' => 'm-wrap span12'],
                    'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('s')
                        ->andWhere("s.status = 1")
                        ->orderBy('s.name','ASC'),
                ])
                ->add('location', EntityType::class, [
                    'required'    => false,
                    'empty_value' => '---Select Location---',
                    'constraints' =>[
                        new NotBlank(message: 'Please enter location')
                    ],
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
            'data_class' => GlobalOption::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'globaloption';
    }

    /**
     * @return mixed
     */
    protected function SyndicateChoiceList()
    {
        return $syndicateTree = $this->em->getSyndicateOptionGroup();

    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}


