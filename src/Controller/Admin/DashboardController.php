<?php

namespace App\Controller\Admin;

use App\Entity\Activite;
use App\Entity\ProfilUtilisateur;
use App\Entity\Recette;
use App\Entity\Repas;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
        $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        $url = $adminUrlGenerator->setController(UserCrudController::class)->generateUrl();

        return $this->redirect($url);

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // 1.1) If you have enabled the "pretty URLs" feature:
        // return $this->redirectToRoute('admin_user_index');
        //
        // 1.2) Same example but using the "ugly URLs" that were used in previous EasyAdmin versions:
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        //
        // if ('jane' === $this->getUser()->getUsername()) {
        //     return $this->redirectToRoute('...');
        // }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('NutriFit');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class)
            ->setController(UserCrudController::class);
        yield MenuItem::linkToCrud('Profils', 'fa fa-id-card', ProfilUtilisateur::class)
            ->setController(ProfilUtilisateurCrudController::class);
        yield MenuItem::linkToCrud('Repas', 'fa fa-utensils', Repas::class)
            ->setController(RepasCrudController::class);
        yield MenuItem::linkToCrud('Activités', 'fa fa-person-running', Activite::class)
            ->setController(ActiviteCrudController::class);
        yield MenuItem::linkToCrud('Recettes', 'fa fa-book', Recette::class)
            ->setController(RecetteCrudController::class);
    }
}
