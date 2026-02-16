<?php

namespace App\Controller;

use App\Entity\ProfilUtilisateur;
use App\Form\ProfilUtilisateurType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function show(): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $profil = $user->getProfilUtilisateur();
        if ($profil === null) {
            return $this->redirectToRoute('profile_edit');
        }

        $this->denyAccessUnlessGranted('PROFIL_VIEW', $profil);

        return $this->render('profile/show.html.twig', [
            'user' => $user,
            'profil' => $profil,
        ]);
    }

    #[Route('/profile/edit', name: 'profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $profil = $user->getProfilUtilisateur();
        if ($profil === null) {
            $profil = new ProfilUtilisateur();
        }

        $this->denyAccessUnlessGranted('PROFIL_EDIT', $profil);
        $form = $this->createForm(ProfilUtilisateurType::class, $profil);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $profil->setUtilisateur($user);
            $entityManager->persist($profil);
            $entityManager->flush();

            return $this->redirectToRoute('profile');
        }

        return $this->render('profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
