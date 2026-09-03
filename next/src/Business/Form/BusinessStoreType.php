<?php

namespace App\Business\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\DomainUser\Entity\Customer;
use App\Business\Entity\BusinessArea;
use App\Business\Entity\Marketing;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Business\Entity\BusinessStore;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BusinessStoreType extends AbstractType
{


    /** @var  $option GlobalOption  */
    public  $option;

    public function __construct(GlobalOption $option)
    {
        $this->option = $option;

    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter store name'],
                'constraints' =>[
                    new NotBlank(message: 'Enter store name')
                ]]
            )
            ->add('mobileNo',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter mobile no'],
                    'constraints' =>[
                        new NotBlank(message: 'Enter store name')
                    ]]
            )
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Enter address'],
                    ]
            )
            ->add('customer', EntityType::class, [
                'required'    => true,
                'class' => Customer::class,
                'property' => 'name',
                'empty_value' => '---Choose a DSR ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[
                    new NotBlank(message: 'Enter DSR')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption ={$this->option->getId()}")
                    ->orderBy("e.name","ASC")
            ])
            ->add('area', EntityType::class, [
                'required'    => true,
                'class' => BusinessArea::class,
                'property' => 'name',
                'empty_value' => '---Choose a area ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[
                    new NotBlank(message: 'Enter area')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.businessConfig ={$this->option->getBusinessConfig()->getId()}")
                    ->orderBy("e.name","ASC")
            ])
            ->add('marketing', EntityType::class, [
                'required'    => true,
                'class' => Marketing::class,
                'property' => 'name',
                'empty_value' => '---Choose a SM ---',
                'attr'=>['class'=>'span12 m-wrap'],
                'constraints' =>[
                    new NotBlank(message: 'Enter SR')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.businessConfig ={$this->option->getBusinessConfig()->getId()}")
                    ->orderBy("e.name","ASC")
            ])


        ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BusinessStore::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'area';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }


}
