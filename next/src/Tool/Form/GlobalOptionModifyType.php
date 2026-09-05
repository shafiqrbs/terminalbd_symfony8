<?php

namespace App\Tool\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Doctrine\ORM\EntityRepository;
use App\Location\Repository\LocationRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class  GlobalOptionModifyType extends AbstractType
{
    protected $location;


    /** @var  LocationRepository */

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->location = $options['location'];

        $userID = (!empty($options['data'])) ? $options['data']->getId():0;
        $optionID = (!empty($options['data'])) ? $options['data']->getId():0;
        $syndicateId = (!empty($options['data']) and $options['data']->getSyndicate()) ? $options['data']->getSyndicate()->getParent()->getId():'';

        if($userID > 0){

            parent::buildForm($builder, $options);
            $builder

                ->add('name', TextType::class, array('attr'=>array('class'=>'m-wrap span12 tooltips','placeholder'=>'Enter Name' , 'data-original-title' =>'Please enter name of organization' , 'data-trigger' => 'hover'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please input required')),
                        new Length(array('max'=>200))
                    )
                ))
                ->add('address', TextareaType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Enter address')
                ))
                ->add('location', EntityType::class, array(
                    'required'    => false,
                    'placeholder' => '---Select Location---',
                    'attr'=>array('class'=>'select2 span12'),
                    'class' => 'App\Location\Entity\Location',
                    'choices'=> $this->LocationChoiceList(),
                    'choice_label' => 'nestedLabel',
                ))
                ->add('mobile', TextType::class, array('attr'=>array('class'=>'m-wrap span12 tooltips','readonly'=> 'readonly','placeholder'=>'Please enter public relational mobile no' , 'data-original-title' =>'Please enter public relational mobile no' , 'data-trigger' => 'hover'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please input required')),
                        new Length(array('max'=>200))
                    )
                ))
                ->add('hotline', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Enter Hotline no')
                ))
                ->add('mobileName', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=> "Enter organization name")))
                ->add('organizationName', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=> "Enter organization name")))
                ->add('mainApp', EntityType::class, array(
                    'required'    => true,
                    'class' => 'App\Tool\Entity\AppModule',
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please select main application')),
                    ),
                    'placeholder' => '---Select Main Application ---',
                    'choice_label' => 'name',
                    'attr'     =>array('id' => '' , 'class' => 'm-wrap span12'),
                    'query_builder' => function(EntityRepository $er){
                        return $er->createQueryBuilder('s')
                            ->andWhere("s.status = 1")
                            ->orderBy('s.name','ASC');
                    },
                ))
                /*->add('syndicate', EntityType::class, array(
                    'required'    => true,
                    'class' => 'App\Tool\Entity\Syndicate',
                    'placeholder' => '---Select Syndicate ---',
                    'choice_label' => 'name',
                    'attr'     =>array('id' => '' , 'class' => 'm-wrap placeholder-no-fix selectbox'),
                    'query_builder' => function(EntityRepository $er){
                            return $er->createQueryBuilder('s')
                                ->andWhere("s.status = 1")
                                ->andWhere("s.parent is null")
                                ->orderBy('s.name','ASC');
                        },
                ))*/

                ->add('linkDomain', EntityType::class, array(
                   'required'    => true,
                   'class' => 'App\Tool\Entity\GlobalOption',
                   'placeholder' => '---Select Link Domain ---',
                   'choice_label' => 'name',
                   'attr'     =>array('id' => '' , 'class' => 'm-wrap span12 select2'),
                   'query_builder' => function(EntityRepository $er) use($optionID){
                           return $er->createQueryBuilder('s')
                               ->andWhere("s.status = 1")
                               ->andWhere("s.id != {$optionID}")
                               ->orderBy('s.name','ASC');
                       },
               ))

                ->add('domain', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Enter domain name'),
                    'constraints' =>array(
	                    new Length(array('max'=>200))
                    )
                ))
	            ->add('domainType', ChoiceType::class, array(
		            'attr'=>array('class'=>'m-wrap span12'),
		            'choices' => array('Select Domain Type' => '', 'Website' => 'website', 'E-commerce' => 'ecommerce', 'Hotel & Restaurant' => 'hotel', 'Convention' => 'convention', 'Doctor' => 'doctor', 'Parlor' => 'parlor', 'Portal' => 'portal', 'Medicine' => 'medicine'),
	            ))
                ->add('file', FileType::class, array('attr'=>array('class'=>'default')))
                ->add('subDomain', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Enter sub-domain name'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please input required')),
                        new Length(array('max'=>200))
                    )
                ))
                ->add('email', TextType::class, array('attr'=>array('class'=>'m-wrap span12')))
                ->add('webMail', TextType::class, array('attr'=>array('class'=>'m-wrap span12','placeholder'=>'Enter your web mail url')))

                /*

                ->add('facebookPageUrl', TextType::class, array('attr'=>array('class'=>'m-wrap span12')))
                ->add('instagramPageUrl', TextType::class, array('attr'=>array('class'=>'m-wrap span12')))
                ->add('customizeDesign')
                ->add('twitterUrl', TextType::class, array('attr'=>array('class'=>'m-wrap span12')))
                ->add('googlePlus', TextType::class, array('attr'=>array('class'=>'m-wrap span12')))
                ->add('smsIntegration')
                ->add('emailIntegration')
                ->add('facebookAds')
                ->add('facebookApps')
                ->add('promotion')
                ->add('googleAds')

                */
                ->add('isSidebar')
                ->add('primaryNumber')
                ->add('isBranch')
                ->add('status', ChoiceType::class, array(
                    'attr'=>array('class'=>'selectbox span12'),
                    'choices' => array('Active' => 1, 'In-active' => '2'),
                ))
                /*
                 * ->add('callBackEmail')
                ->add('callBackContent', TextareaType::class, array('attr'=>array('class'=>' m-wrap span12','rows'=>4)))
                ->add('callBackNotify')
                ->add('leaveEmail')
                ->add('leaveContent', TextareaType::class, array('attr'=>array('class'=>' m-wrap span12','rows'=>4)))
                */

            ;
            /* $builder->add('contactPage', ContactOpeningType::class);*/
            //$builder->add('templateCustomize', TemplateSidebarType::class);
            $builder->add('siteSetting', SiteSettingType::class, ['syndicateId' => $syndicateId]);

        }else{

            $builder

                ->add('name', TextType::class, array('attr'=>array('class'=>'m-wrap tooltips','placeholder'=>'Enter Name' , 'data-original-title' =>'Please enter name of organization' , 'data-trigger' => 'hover'),
                    'constraints' =>array(
                        new NotBlank(array('message'=>'Please input required')),
                        new Length(array('max'=>200))
                    )
                ))

                ->add('syndicate', EntityType::class, array(
                'required'    => true,
                'class' => 'App\Tool\Entity\Syndicate',
                'placeholder' => '---Select Syndicate ---',
                'choice_label' => 'name',
                'attr'     =>array('id' => '' , 'class' => 'm-wrap placeholder-no-fix selectbox span12'),
                'query_builder' => function(EntityRepository $er){
                        return $er->createQueryBuilder('s')
                            ->andWhere("s.status = 1")
                            ->andWhere("s.parent is null")
                            ->orderBy('s.name','ASC');
                    },
            ))
            ->add('status', CheckboxType::class, array(
                        'attr'=>array('class'=>'')
                    )
            );

        }

    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'location' => null,
        ]);

        $resolver->setDefaults(array(
            'data_class' => 'App\Tool\Entity\GlobalOption'
        ));
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'globaloption';
    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->location->getLocationOptionGroup();

    }
}
