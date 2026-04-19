<?php

namespace App\Form;

use App\Entity\Projet;
use App\Entity\Utilisateur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProjetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom du projet',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: Refonte site web Stratix',
                ],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'attr'  => [
                    'class'       => 'form-control',
                    'rows'        => 3,
                    'placeholder' => 'Décrivez les objectifs du projet...',
                ],
            ])
            ->add('dateDebut', DateType::class, [
                'label'  => 'Date de début',
                'widget' => 'single_text',
                'html5'  => true,
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('dateFin', DateType::class, [
                'label'  => 'Date de fin',
                'widget' => 'single_text',
                'html5'  => true,
                'attr'   => ['class' => 'form-control'],
            ])
            ->add('budget', NumberType::class, [
                'label' => 'Budget (DT)',
                'attr'  => [
                    'class'       => 'form-control',
                    'placeholder' => 'Ex: 15000',
                ],
            ])
            ->add('statut', ChoiceType::class, [
                'label'       => 'Statut',
                'choices'     => [
                    'Planifié' => 'Planifié',
                    'En cours' => 'En cours',
                    'Terminé'  => 'Terminé',
                    'Annulé'   => 'Annulé',
                ],
                'placeholder' => false,
                'empty_data'  => 'Planifié',
                'required'    => true,
                'attr'        => ['class' => 'form-select'],
            ])
            ->add('responsable', EntityType::class, [
                'class'        => Utilisateur::class,
                'label'        => 'Responsable du projet',
                'choice_label' => fn(Utilisateur $u) => $u->getPrenom() . ' ' . $u->getNom(),
                'placeholder'  => '-- Choisir un responsable --',
                'attr'         => ['class' => 'form-select'],
            ])
            ->add('membres', EntityType::class, [
                'class'        => Utilisateur::class,
                'label'        => "Membres de l'équipe",
                'choice_label' => fn(Utilisateur $u) => $u->getPrenom() . ' ' . $u->getNom(),
                'multiple'     => true,
                'required'     => false,
                'attr'         => ['class' => 'form-select tom-select-it'],
            ])
            ->add('cahierDesChargesFile', FileType::class, [
                'label'    => 'Cahier des charges',
                'mapped'   => false, // toujours false, pas en BDD
                'required' => false,
                'attr'     => [
                    'class' => 'form-control',
                    'id'    => 'cahierFile',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Projet::class]);
    }
}