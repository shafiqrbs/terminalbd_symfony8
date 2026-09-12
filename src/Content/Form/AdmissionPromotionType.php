<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdmissionPromotionType extends AbstractType
{
    protected $user;
    protected $branch;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->user = $options['user'];
        $this->branch = $options['branch'];

        $builder
            ->add('amount',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter payment amount'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('position', ChoiceType::class, [
                'required'      =>true,
                'attr'=>['class'=>'selectbox'],
                'choices' => ['Top' => 'top', 'Right' => 'right', 'Admission Page' => 'admission'],
            ])
            ->add('paymentStatus', ChoiceType::class, [
                'required'      =>true,
                'attr'=>['class'=>'selectbox'],
                'choices' => ['Receive' => 'Receive', 'Due' => 'Due', 'Others' => 'Others'],
            ])
            ->add('promotionStartDate',DateType::class, ['attr'=>['class'=>'selectbox']])
            ->add('promotionEndDate',DateType::class, ['attr'=>['class'=>'selectbox']]);

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null,
            'branch' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => 'App\Content\Entity\Admission'
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_admission';
    }
}
