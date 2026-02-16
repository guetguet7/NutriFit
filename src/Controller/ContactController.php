<?php

namespace App\Controller;

use App\Dto\ContactDto;
use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

final class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $contact = new ContactDto();
        $form = $this->createForm(ContactType::class, $contact);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = (new Email())
                ->from('no-reply@nutrifit.local')
                ->to($this->getParameter('contact_to'))
                ->replyTo($contact->email)
                ->subject('Nouveau message de contact')
                ->text(
                    "Nom: {$contact->name}\n"
                    . "Email: {$contact->email}\n\n"
                    . $contact->message
                );

            $mailer->send($email);
            $this->addFlash('success', 'Votre message a bien été envoyé.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
