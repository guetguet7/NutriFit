<?php

namespace App\Form;

use App\Entity\ProfilUtilisateur;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ProfilUtilisateurType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('sexe', ChoiceType::class, [
                'choices' => [
                    'Homme' => 'homme',
                    'Femme' => 'femme',
                ],
                'expanded' => true,
            ])
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
                'required' => false,
            ])
            ->add('age', IntegerType::class)
            ->add('tailleCm', IntegerType::class, [
                'label' => 'Taille (cm)',
            ])
            ->add('poidsKg', NumberType::class, [
                'label' => 'Poids (kg)',
                'scale' => 2,
            ])
            ->add('niveauActivite', ChoiceType::class, [
                'choices' => [
                    'Sédentaire' => 'sédentaire',
                    'Léger' => 'léger',
                    'Modéré' => 'modéré',
                    'Actif' => 'actif',
                ],
                'label' => 'Niveau d\'activité',
            ])
            ->add('objectifType', ChoiceType::class, [
                'choices' => [
                    'Perte' => 'perte',
                    'Maintien' => 'maintien',
                    'Prise' => 'prise',
                ],
                'label' => 'Objectif',
                'expanded' => true,
            ])
            ->add('poidsCibleKg', NumberType::class, [
                'label' => 'Poids cible (kg)',
                'scale' => 2,
                'required' => false,
            ])
            ->add('rythmePerteKgSemaine', NumberType::class, [
                'label' => 'Rythme de perte (kg/semaine)',
                'scale' => 2,
                'required' => false,
            ])
            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProfilUtilisateur::class,
        ]);
    }
}
