<?php

namespace App\Restaurant\Form;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Restaurant\Entity\Particular;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Restaurant\Entity\InvoiceParticular;
use App\Restaurant\Entity\RestaurantConfig;
use App\Restaurant\Repository\ParticularRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class RestaurantParticularType extends AbstractType
{
    protected $particular;


    /** @var  RestaurantConfig */
    private $restaurantConfig;

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->restaurantConfig = $options['restaurantConfig'];
        $this->particular = $options['particular'];

        $builder

            ->add('particular', EntityType::class, [
                'class'     => Particular::class,
                'group_by'  => 'category.name',
                'choice_label'  => 'codeName',
                'placeholder' => '-Choose product name -',
                'attr'=>['class'=>'span12 m-wrap select2 particular'],
                'choice_translation_domain' => true,
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join("e.category",'c')
                    ->where("c.status=1")
                    ->andWhere("e.status=1")
                    ->andWhere("e.restaurantConfig ={$this->restaurantConfig}")
                    ->orderBy("c.sorting", "ASC")
                    ->addOrderBy("e.sorting", "ASC")
            ])
            /*  ->add('salesPrice', ChoiceType::class, array(
                'label' => 'Choose your car',
                'choices' => $this->ParticularChoiceList(),
            ))*/;

    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'restaurantConfig' => null,
            'particular' => null,
        ]);

        $resolver->setDefaults([
            'data_class' => InvoiceParticular::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'restaurant_item';
    }

    protected function ParticularChoiceList()
    {
        return $syndicateTree = $this->particular->getParticularOptionGroup($this->restaurantConfig);

    }

}
