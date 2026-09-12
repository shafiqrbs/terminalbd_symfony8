<?php

namespace App\Accounting\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\User\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Accounting\Entity\SalarySetting;
use Doctrine\ORM\EntityRepository;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class VoucherFormType extends AbstractType
{

    public  $globalOption;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->globalOption = $options['globalOption'];

        $builder

            ->add('basicAmount',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'add payment amount BDT'],
                'constraints' =>[
                    new NotBlank(message: 'Add basic amount')
                ]])
            ->add('bonusAmount',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'']])
            ->add('otherAmount',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'']])
            ->add('advanceAmount',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'']])
            ->add('remark',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'']])
            ->add('effectedMonth',DateType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>''],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'years'=> ['2016', '2017', '2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025'],
                'widget' => 'choice',
                // this is actually the default format for single_text
                'format' => 'dd-MM-yyyy',

            ])
            ->add('user', EntityType::class, [
                'required'    => true,
                'class' => User::class,
                'placeholder' => '---Choose a employee---',
                'choice_label' => 'userFullName',
                'attr'=>['class'=>'span12 select2'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.globalOption =".$this->globalOption->getId()),
            ]);
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'globalOption' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => SalarySetting::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'appstore_bundle_accountingBundle_salarysetting';
    }
}
