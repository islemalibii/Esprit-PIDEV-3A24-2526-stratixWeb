<?php

namespace App\Form;

use App\Entity\EventFeedback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class FeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'choices' => [
                    '⭐ 1 - Très mauvais'  => 1,
                    '⭐⭐ 2 - Mauvais'      => 2,
                    '⭐⭐⭐ 3 - Moyen'       => 3,
                    '⭐⭐⭐⭐ 4 - Bien'       => 4,
                    '⭐⭐⭐⭐⭐ 5 - Excellent' => 5,
                ],
                'expanded' => false,
                'multiple' => false,
                'placeholder' => 'Choisir une note',
            ])
            ->add('commentaire', TextareaType::class, [
                'attr' => [
                    'placeholder' => 'Partagez votre expérience...',
                    'rows' => 4,
                ],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => EventFeedback::class,
        ]);
    }
}