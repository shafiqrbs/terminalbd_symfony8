<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Location;
use App\User\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DomainUser\Entity\Customer;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class CustomerType extends AbstractType
{
    protected $globalOption;
    protected $location;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Customer name'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter customer name')
                    ]]
            )
            ->add('discountPercent',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Discount Percent(%)']])
            ->add('openingBalance',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Opening Balance']])
            ->add('alternativeMobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Alternative mobile no']])
            ->add('customerGroup',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Customer Group','list'=>'customerGroups']])
            ->add('proprietorName',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Proprietor Name']])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12','autocomplete'=>'off','placeholder'=>'Mobile no'],
                    'constraints' =>[
                        new NotBlank(message: 'Please enter mobile no')
                    ]]
            )
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Email address']])
            ->add('customerGroup',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Customer Group','list'=>'customerGroups']])
            ->add('referenceId',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Old reference no']])
            ->add('creditLimit',TextType::class, ['attr'=>['class'=>'m-wrap span12 ','placeholder'=>'Credit limit']])
            ->add('status')
            ->add('address',TextareaType::class, ['attr'=>['class'=>'m-wrap span12 ','rows'=>8,'placeholder'=>'Enter customer address']])
            ->add('location', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select Location---',
                'attr'=>['class'=>' m-wrap span12'],
                'class' => Location::class,
                'choices'=> $this->LocationChoiceList(),
                'choice_label' => 'nestedLabel',
            ])
            ->add('marketing', EntityType::class, [
                'required'    => false,
                'class' => User::class,
                'placeholder' => '---Choose a marketing executive---',
                'choice_label' => 'userFullName',
                'attr'=>['class'=>'select2 span12 m-wrap'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')
                    ->where("u.isDelete != 1")
                    ->andWhere("u.globalOption =".$this->globalOption->getId())
                    ->andWhere("u.userGroup = 'marketing'")
                    ->orderBy("u.username", "ASC"),
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
            'location' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => Customer::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'customer';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}
