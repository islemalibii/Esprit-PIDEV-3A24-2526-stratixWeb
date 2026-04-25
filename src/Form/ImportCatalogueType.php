<?php

// src/Form/ImportCatalogueType.php
namespace App\Form;

use App\Entity\Fournisseur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImportCatalogueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('fournisseur', EntityType::class, [
                'class' => Fournisseur::class,
                'choice_label' => 'nom',
                'label' => 'Sélectionner le Fournisseur',
                'placeholder' => 'Choisir un fournisseur...',
            ])
            ->add('fichier', FileType::class, [
                'label' => 'Fichier Catalogue (Excel ou CSV)',
                'mapped' => false, // car ce n'est pas un champ de l'entité
                'required' => true,
            ]);
    }
}