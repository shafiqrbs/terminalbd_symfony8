<?php

namespace App\Tool\Form;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Tool\Entity\Syndicate;
use App\Location\Entity\Location;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Tool\Entity\GlobalOption;
use App\Tool\Repository\SyndicateRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SearchType extends AbstractType
{
    protected $em;


    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->em = $options['em'];


            $builder

                ->add('name',TextType::class, ['attr'=>['class'=>'m-wrap span12 tooltips','placeholder'=>'search keyword....'],
                    'constraints' =>[]
                ])

                ->add('syndicate', EntityType::class, [
                    'required'    => true,
                    'placeholder' => '---Select Business Sector---',
                    'attr'=>['class'=>'select2 span12'],
                    'class' => Syndicate::class,
                    'choices'=> $this->SyndicateChoiceList(),
                    'choice_label' => 'nestedLabel',
                ])

                ->add('location', EntityType::class, [
                    'required'    => false,
                    'placeholder' => '---Select Business Sector---',
                    'class' => Location::class,
                    'choices'=> $this->LocationChoiceList(),
                    'choice_label' => 'nestedLabel',
                ]);

    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'em' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => GlobalOption::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_toolbundle_globaloption';
    }

    /**
     * @return mixed
     */
    protected function SyndicateChoiceList()
    {
        return $syndicateTree = $this->em->getSyndicateOptionGroup();

    }

    protected function LocationChoiceList()
    {
        return $syndicateTree = $this->em->getLocationOptionGroup();

    }
}

