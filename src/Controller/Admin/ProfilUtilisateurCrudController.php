<?php

namespace App\Controller\Admin;

use App\Entity\ProfilUtilisateur;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ProfilUtilisateurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProfilUtilisateur::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('utilisateur'),
            TextField::new('pseudo')->hideOnIndex(),
            ChoiceField::new('sexe')->setChoices([
                'Homme' => 'homme',
                'Femme' => 'femme',
            ]),
            IntegerField::new('age'),
            IntegerField::new('tailleCm', 'Taille (cm)'),
            NumberField::new('poidsKg', 'Poids (kg)'),
            ChoiceField::new('niveauActivite', 'Niveau d\'activité')->setChoices([
                'Sedentaire' => 'sedentaire',
                'Leger' => 'leger',
                'Modere' => 'modere',
                'Actif' => 'actif',
            ]),
            ChoiceField::new('objectifType', 'Objectif')->setChoices([
                'Perte' => 'perte',
                'Maintien' => 'maintien',
                'Prise' => 'prise',
            ]),
            NumberField::new('poidsCibleKg', 'Poids cible (kg)')->hideOnIndex(),
            NumberField::new('rythmePerteKgSemaine', 'Rythme perte (kg/semaine)')->hideOnIndex(),
        ];
    }
}
