<?php

namespace App\Content\Form;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Content\Entity\SiteTeam;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class SiteTeamType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter Name'],
                'constraints' =>[
                    new NotBlank(message: 'Please input required'),
                    new Length(max: 200)
                ]
            ])
            ->add('designation',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter person designation']])
            ->add('file',FileType::class, ['attr'=>['class'=>'default']])
            ->add('content',TextareaType::class, ['attr'=>['class'=>'ckeditor m-wrap span12','rows'=>15]])
	        ->add('businessSector', ChoiceType::class, [
		        'attr'=>['class'=>'m-wrap span12'],
		        'choices' => ['Portal' => 'portal', 'Education' => 'education', 'E-commerce' => 'ecommerce', 'Reservation' => 'reservation', 'Fleet Management' => 'fleet'],
	        ])
			->add('status');
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SiteTeam::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'siteteam';
    }
}
