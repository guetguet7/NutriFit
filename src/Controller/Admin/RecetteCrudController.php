<?php

namespace App\Controller\Admin;

use App\Entity\Recette;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class RecetteCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Recette::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            IntegerField::new('spoonacularId', 'Spoonacular ID'),
            TextField::new('titre'),
            TextField::new('imageUrl')->hideOnIndex(),
            IntegerField::new('caloriesParPortion', 'Calories'),
            IntegerField::new('servings'),
            TextEditorField::new('rawJson')->onlyOnDetail(),
            DateTimeField::new('updatedAt', 'Maj'),
        ];
    }
}
