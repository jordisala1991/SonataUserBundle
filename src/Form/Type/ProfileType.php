<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\LocaleType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TimezoneType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    private $class;

    public function __construct(string $class)
    {
        $this->class = $class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $genderOptions = [
            'label' => 'form.label_gender',
            'choices' => call_user_func([$this->class, 'getGenderList']),
            'required' => true,
            'translation_domain' => $this->getTranslationDomain(),
        ];

        // NEXT_MAJOR: Remove this when dropping support for SF 2.8
        if (method_exists(FormTypeInterface::class, 'setDefaultOptions')) {
            $genderOptions['choices_as_values'] = true;
        }

        $builder
            ->add('gender', ChoiceType::class, $genderOptions)
            ->add('firstname', null, [
                'label' => 'form.label_firstname',
                'required' => false,
            ])
            ->add('lastname', null, [
                'label' => 'form.label_lastname',
                'required' => false,
            ])
            ->add('dateOfBirth', BirthdayType::class, [
                'label' => 'form.label_date_of_birth',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('website', UrlType::class, [
                'label' => 'form.label_website',
                'required' => false,
            ])
            ->add('biography', TextareaType::class, [
                'label' => 'form.label_biography',
                'required' => false,
            ])
            ->add('locale', LocaleType::class, [
                'label' => 'form.label_locale',
                'required' => false,
            ])
            ->add('timezone', TimezoneType::class, [
                'label' => 'form.label_timezone',
                'required' => false,
            ])
            ->add('phone', null, [
                'label' => 'form.label_phone',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->class,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'sonata_user_profile';
    }

    public function getName(): string
    {
        return $this->getBlockPrefix();
    }
}
