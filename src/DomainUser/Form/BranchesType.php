<?php

namespace App\DomainUser\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\User\Entity\User;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\DomainUser\Entity\Branches;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class BranchesType extends AbstractType
{
    protected $location;


    /** @var GlobalOption */
    public  $globalOption;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];
        $this->location = $options['location'];

        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Branch name'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter branch name ')
            ]])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Branch address'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter  branch address')
            ]])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Add  mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Please enter  mobile no')
            ]])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Add  email address']])
            ->add('branchManager', EntityType::class, [
                'expanded'      =>false,
                'multiple'      =>false,
                'required'    => true,
                'class' => User::class,
                'choice_label' => 'username',
                'attr'=>['class'=>'span12 select2'],
                'placeholder' => '---Choose assign manager ---',
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('u')
                    ->andWhere("u.globalOption =".$this->globalOption->getId())
                    ->orderBy("u.username", "ASC")
            ])
            ->add('location', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select Location---',
                'attr'=>['class'=>'select2 span12'],
                'class' => Location::class,
                'constraints' =>[
                    new NotBlank(message: 'Select user location')
                ],
                'choices'=> $this->LocationChoiceList(),
                'choice_label' => 'nestedLabel',
            ])
            ->add('status');
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
            'data_class' => Branches::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_domainUserbundle_branches';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}
