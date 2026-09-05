<?php

namespace App\Content\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Media\Entity\PhotoGallery;
use App\Location\Entity\Location;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use App\Content\Entity\ModuleCategory;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Content\Entity\Page;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class EventType extends AbstractType
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
            ->add('organization',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter organization name']])
            ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter email address']])
            ->add('website',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter website']])
            ->add('price',TextType::class, ['attr'=>['class'=>'m-wrap span12 numeric','placeholder'=>'Enter event price']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'ckeditor m-wrap span12','rows' => 12]])
            ->add('address',TextareaType::class, ['attr'=>['class'=>'m-wrap span12','rows' => 9]])
            ->add('additionalPhone',TextType::class, ['attr'=>['class'=>'m-wrap span12']])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('startDate',DateType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>''],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'years'=> ['2015', '2016', '2017', '2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025'],
                'widget' => 'single_text',
                // this is actually the default format for single_text
                'format' => 'yyyy-MM-dd',

            ])
            ->add('endDate',DateType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>''],
                'constraints' =>[
                    new NotBlank(message: 'Please input required')
                ],
                'years'=> ['2015', '2016', '2017', '2018', '2019', '2020', '2021', '2022', '2023', '2024', '2025'],
                'widget' => 'single_text',
                // this is actually the default format for single_text
                'format' => 'yyyy-MM-dd',

            ])
            ->add('startHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10', 'data-format' => 'hh:mm A','placeholder'=>'Start hour']])
            ->add('endHour',TextType::class, ['attr'=>['class'=>'m-wrap small clockface_1 span10', 'data-format' => 'hh:mm A', 'placeholder'=>'End hour']])
            ->add('photo_gallery', EntityType::class, [
                'required'    => false,
                'class' => PhotoGallery::class,
                'placeholder' => '---Select Photo Gallery---',
                'choice_label' => 'name',
                'attr'=>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
                    ->andWhere("o.status = 1")
                    ->andWhere("o.globalOption = $this->globalOption ")
                    ->orderBy('o.name','ASC'),
            ])
            ->add('latitude',TextType::class, ['attr'=>['class'=>'m-wrap span12']])
            ->add('longitude',TextType::class, ['attr'=>['class'=>'m-wrap span12']])
            ->add('location', EntityType::class, [
                'required'    => false,
                'placeholder' => '---Select Location---',
                'attr'=>['class'=>'select2 span12'],
                'class' => Location::class,
                'constraints' =>[
                    new NotBlank(message: 'Select customer location')
                ],
                'choices'=> $this->LocationChoiceList(),
                'choice_label' => 'nestedLabel',
            ])
            ->add('eventType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'placeholder' => '---Select Event Type ---',
                'expanded'      =>false,
                'required'    => false,
                'multiple'      =>false,
                'choices' => ['Ongoing' => 'Ongoing', 'Upcoming' => 'Upcoming'],
            ])
            ->add('moduleCategory', EntityType::class, [
                'required'    => false,
                'class' => ModuleCategory::class,
                'placeholder' => '---Select event category ---',
                'choice_label' => 'name',
                'attr'=>['class'=>'span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('o')
	                    ->join('o.module', 'module')
	                    ->where("o.status = 1")
	                    ->andWhere("module.slug = :module")
	                    ->setParameter('module', 'event')
                    ->andWhere("o.globalOption =".$this->globalOption)
                    ->orderBy('o.name','ASC'),
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
            'data_class' => Page::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_contentbundle_page';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}
