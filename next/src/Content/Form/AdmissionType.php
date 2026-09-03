<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class AdmissionType extends AbstractType
{

    public function __construct(private $user, private $branch)
    {
    }
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter title'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('contactPerson',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter contact person'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('mobile',TextType::class, ['attr'=>['class'=>'m-wrap span12 mobile','placeholder'=>'Enter mobile no'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('phone',TextType::class, ['attr'=>['class'=>'m-wrap span12 phone']])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter email address'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),

                ]
            ])
            ->add('isPayment')
            ->add('isOnlineRegistration')
            ->add('shifting', ChoiceType::class, [
                'multiple'      =>true,
                'expanded'      =>true,
                'attr'=>['class'=>''],
                'choices' => ['Morning' => 'Morning',  'Afternoon' => 'Afternoon', 'Evening' => 'Evening'],
            ])
            ->add('tuitionFee',TextType::class, ['attr'=>['class'=>'m-wrap span12 ']])
            ->add('classDuration',TextType::class, ['attr'=>['class'=>'m-wrap span12 ']])
            ->add('coursePeriod',TextType::class, ['attr'=>['class'=>'m-wrap span12 ']])
            ->add('qualification',TextType::class, ['attr'=>['class'=>'m-wrap span12 ']])

            ->add('content',TextareaType::class, ['attr'=>['class'=>'wysihtml5 m-wrap span12','rows'=>8]])
            ->add('address',TextType::class, ['attr'=>['class'=>'m-wrap span12']])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('startDate',DateType::class, ['attr'=>['class'=>'selectbox']])
            ->add('startHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10']])
            ->add('endHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10']])
            ->add('endDate',DateType::class, ['attr'=>['class'=>'selectbox']])
            ->add('courseLevel', EntityType::class, [
                'required'      => false,
                'class'         => 'App\Tool\Entity\CourseLevel',
                'empty_value' => '---Select course level---',
                'property'      => 'name',
                'attr'          =>['class'=>'m-wrap span10 selectbox'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere("c.status = 1")
                    ->orderBy('c.id','ASC')
            ])
            ->add('course', EntityType::class, [
                'required'      => false,
                'class'         => 'App\Tool\Entity\Course',
                'property'      => 'name',
                'empty_value' => '---Select course---',
                'attr'          =>['class'=>'selectbox span10'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('c')
                    ->andWhere("c.status = 1")
                    ->orderBy('c.id','ASC')
            ])
            ->add('isPromotion')
            ->add('status');
        if($this->branch == 'branch') {

            $builder->add('branches', EntityType::class, [
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'class' => 'App\Content\Entity\Branch',
                'property' => 'name',
                'attr' => ['class' => ''],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('b')
                    ->where("b.status = 1")
                    ->andWhere("b.user = $this->user ")
            ]);
        }


    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
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
