<?php

/*
 * This file is part of the FOSUserBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\User\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\User\Entity\Profile;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;


class ProfileType extends AbstractType
{


    public function __construct(private readonly LocationRepository $location)
    {
    }


    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
           $builder
                ->add('name',TextType::class, ['required' => false,'attr'=>['class'=>'m-wrap span12 form-control','placeholder'=>'Enter your full name'],
                'constraints' =>[
                    new NotBlank(message: 'Enter your full name required'),
                    new Length(max: 200)
                    ]]
                )
               ->add('mobile',TextType::class, ['attr'=>['class'=>'mobile form-control','placeholder'=>'Enter your mobile no'],
                       'constraints' =>[
                           new NotBlank(message: 'Enter your mobile no required'),
                           new Length(max: 200)
                       ]]
               )
               /*->add('location', 'entity', array(
                   'required'    => false,
                   'empty_value' => '---Select Location---',
                   'attr'=>array('class'=>'select-location span12 m-wrap form-control'),
                   'class' => 'App\Location\Entity\Location',
                   'constraints' =>array(
                       new NotBlank(array('message'=>'Select user location'))
                   ),
                   'choices'=> $this->LocationChoiceList(),
                   'choices_as_values' => true,
                   'choice_label' => 'nestedLabel',
               ))*/
                ->add('address',TextType::class, ['required' => false,'attr'=>['class'=>'m-wrap span12 form-control','placeholder'=>'Enter your full address']])
                ->add('termsConditionAccept',CheckboxType::class, [
                    'attr'=>['class'=>''],
                    'constraints' =>[
                        new NotBlank(message: 'Must need to accept terms & condition'),
                    ]
                ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
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
}
