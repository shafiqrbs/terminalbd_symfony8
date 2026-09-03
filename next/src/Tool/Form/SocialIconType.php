<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SocialIconType extends AbstractType
{


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {


            $builder


                ->add('email',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter  e-mail address']])
                ->add('webMail',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter web mail url']])
                ->add('facebookPageUrl',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter facebook page url']])
                ->add('twitterUrl',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter web twitter url']])
                ->add('instagramPageUrl',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter instagram url']])
                ->add('googlePlus',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter google plus url']])
                ->add('youtube',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter youtube url']])
                ->add('linkedin',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter linkedin id & url']])
                ->add('skype',TextType::class, ['attr'=>['class'=>'m-wrap span12','placeholder'=>'Enter skype id & url']])
              ;



    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => GlobalOption::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'social';
    }


}
