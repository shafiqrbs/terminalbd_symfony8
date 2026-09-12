<?php

namespace App\Inventory\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Location\Entity\Country;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Inventory\Entity\Vendor;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class VendorType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder

            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter vendor name']])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter vendor mobile no']])
            ->add('companyName',TextType::class, ['attr'=>['class'=>'m-wrap span9','placeholder'=>'Enter company name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')]
            ])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter vendor address']])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter email address']])
            ->add('country', EntityType::class, [
                'required'      => true,
                'expanded'      =>false,
                'placeholder' => 'Choose a country',
                'class' => Country::class,
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('m')
                    ->orderBy('m.name','ASC'),
            ])
            ->add('vendorCode',TextType::class, ['attr'=>['class'=>'m-wrap span3','placeholder'=>'Short code']
            ,'constraints' =>[
                    new NotBlank(message: 'Please input required')]
            ])
            ->add('status');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Vendor::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_inventorybundle_vendor';
    }
}
