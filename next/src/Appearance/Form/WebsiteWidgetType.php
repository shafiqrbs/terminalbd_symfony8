<?php

namespace App\Appearance\Form;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Appearance\Entity\Menu;
use Doctrine\ORM\EntityRepository;
use App\Content\Entity\ModuleCategory;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use App\Content\Entity\Page;
use App\Tool\Entity\Module;
use App\Appearance\Entity\JsFeature;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Appearance\Entity\FeatureWidget;
use App\Tool\Entity\GlobalOption;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class WebsiteWidgetType extends AbstractType
{

    public function __construct(private readonly GlobalOption $globalOption)
    {
        $this->globalId = $this->globalOption->getId();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder

            ->add('menu', EntityType::class, [
                'required'    => false,
                'class' => Menu::class,
                'empty_value' => '---Select Menu Page---',
                'property' => 'menu',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->orderBy('e.menu','ASC'),
            ])
            ->add('moduleCategory', EntityType::class, [
                'required'    => false,
                'multiple'    => true,
                'class' => ModuleCategory::class,
                'empty_value' => '---Select page---',
                'property' => 'name',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.globalOption =".$this->globalOption->getId())
                    ->orderBy('e.name','ASC'),
            ])
            ->add('position', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12 select2 '],
                'empty_value' => '---Select Position---',
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => [
                    'header'                => 'Header',
                    'body-top'                  => 'Body Top',
                    'body-bottom'               => 'Body Bottom',
                    'sidebar-top'               => 'Sidebar Top',
                    'sidebar-bottom'            => 'Sidebar Bottom',
                    'footer'                    => 'Footer',
                    'mobile'                    => 'Mobile',
                    'mobile-intro'              => 'Mobile Intro',
                ],
            ])
            ->add('pageFeatureName',TextType::class, ['attr'=>['class'=>'span12 m-wrap']])
            ->add('moduleFeatureName',TextType::class, ['attr'=>['class'=>'span12 m-wrap']])
            ->add('moduleShowLimit',NumberType::class, ['attr'=>['class'=>'span12 m-wrap']])
            ->add('moduleShowType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => [
                    'list'                => 'List',
                    'grid'                => 'Grid',
                    'slider'              => 'Slider',
                ],
            ])

            ->add('pageShowType', ChoiceType::class, [
                'attr'=>['class'=>'m-wrap span12'],
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => [
                    'list'                => 'List',
                    'grid'                => 'Grid',
                    'slider'              => 'Slider',
                ],
            ])


            ->add('page', EntityType::class, [
                'required'    => false,
                'multiple'    => true,
                'class' => Page::class,
                'empty_value' => '---Select Page---',
                'property' => 'name',
                'attr'=>['class'=>'m-wrap span12 select2'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->andWhere("e.module = 12")
                    ->andWhere("e.globalOption = $this->globalId")
                    ->orderBy('e.name','ASC'),
            ])

            ->add('module', EntityType::class, [
                'required'    => false,
                'multiple'    => false,
                'class' => Module::class,
                'empty_value' => '---Select page module---',
                'property' => 'name',
                'attr'=>['class'=>'m-wrap span12'],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->join('e.nav' ,'n')
                    ->where("e.status = 1")
                    ->andWhere("e.isSingle != 1")
                    ->andWhere("n.module != 12")
                    ->andWhere("n.module is not NULL")
                    ->andWhere("n.globalOption = $this->globalId")
                    ->orderBy('e.name','ASC'),
            ])

            ->add('jsFeature', EntityType::class, [
                'required'    => false,
                'class' => JsFeature::class,
                'empty_value' => '---Select Any Feature---',
                'property' => 'name',
                'attr'=>['class'=>'m-wrap span12 '],
                'query_builder' => fn(EntityRepository $er) => $er->createQueryBuilder('e')
                    ->where("e.status = 1")
                    ->orderBy('e.name','ASC'),
            ])

            ->add('content',TextareaType::class, ['attr'=>['class'=>'span12 m-wrap ckeditor','rows' => 6]])
            ->add('sliderFeature', ChoiceType::class, [
                'attr'=>['class'=>'span12  m-wrap targetTo'],
                'empty_value' => '---Slider with Feature ---',
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => [
                    'category'          => 'Category',
                    'brand'             => 'Brand',
                    'promotion'         => 'Promotion',
                    'featureProduct'    => 'FeatureProduct',
                    'tag'               => 'Tag',
                    'discount'          => 'Discount'
                ],
            ])
            ->add('sliderFeaturePosition', ChoiceType::class, [
                'attr'=>['class'=>'span12  m-wrap'],
                'empty_value' => '---Feature Position ---',
                'expanded'      =>false,
                'multiple'      =>false,
                'choices' => [
                    'left'               => 'Left',
                    'right'             => 'Right',
                ],
            ])
            ;
    }
    
    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FeatureWidget::class
        ]);
    }

    /**
     * @return string
     */
    public function getBlockPrefix(): string
    {
        return 'setting_bundle_appearancebundle_featurewidget';
    }

    /**
     * @return mixed
     */
    protected function categoryChoiceList()
    {

        return $categoryTree = $this->category->getUseInventoryItemCategory($this->globalOption->getInventoryConfig());

    }

}
