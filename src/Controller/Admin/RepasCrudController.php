<?php

namespace App\Controller\Admin;

use App\Entity\Repas;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RepasCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Repas::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('utilisateur'),
            DateTimeField::new('dateRepas', 'Date'),
            TextField::new('typeRepas', 'Type'),
            IntegerField::new('totalCalories', 'Calories'),
            TextField::new('source'),
            TextEditorField::new('notes')->hideOnIndex(),
            AssociationField::new('elementsRepas')->onlyOnDetail(),
        ];
    }
}
