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
            $name = (string) ($contact->name ?? '');
            $emailAddress = (string) ($contact->email ?? '');
            $messageBody = (string) ($contact->message ?? '');

            $email = (new Email())
                ->from('no-reply@nutrifit.local')
                ->to($this->getParameter('contact_to'))
                ->replyTo($emailAddress)
                ->subject('Nouveau message de contact')
                ->text(
                    "Nom: {$name}\n"
                    . "Email: {$emailAddress}\n\n"
                    . $messageBody
                );

            $mailer->send($email);
            $this->addFlash('success', 'Votre message a bien été envoyé.');

            return $this->redirectToRoute('app_contact');
        }

        $statusCode = $form->isSubmitted() && !$form->isValid()
            ? Response::HTTP_UNPROCESSABLE_ENTITY
            : Response::HTTP_OK;

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ], new Response('', $statusCode));
    }
}
