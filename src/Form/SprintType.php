<?php

namespace App\Form;

use App\Entity\Sprint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SprintType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'NOM DU SPRINT',
                'attr' => ['class' => 'form-control rounded-3', 'placeholder' => 'Ex: Phase Alpha']
            ])
            ->add('dateDebut', DateType::class, [
                'label' => 'DATE DE DÉBUT',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control rounded-3']
            ])
            ->add('dateFin', DateType::class, [
                'label' => 'DATE DE FIN',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control rounded-3']
            ])
            ->add('statut', ChoiceType::class, [
                'label' => 'STATUT ACTUEL',
                'choices'  => [
                    'En attente' => 'En attente',
                    'En cours'   => 'En cours',
                    'Terminé'    => 'Terminé',
                    'Bloqué'     => 'Bloqué',
                ],
                'attr' => ['class' => 'form-select rounded-3']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sprint::class,
        ]);
    }
}