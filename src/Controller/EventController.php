<?php
namespace App\Controller;

use App\Entity\Event;
use App\Entity\Reservation;
use App\Form\ReservationType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventController extends AbstractController
{
    #[Route('/', name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $eventRepository->findAll(),
        ]);
    }

    #[Route('/event/{id}', name: 'app_event_show', methods: ['GET', 'POST'])]
    public function show(Event $event, Request $request, EntityManagerInterface $entityManager): Response
    {
        $reservation = new Reservation();
        $reservation->setEvent($event);
        $form = $this->createForm(ReservationType::class, $reservation);
        
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $currentReservations = count($event->getReservations());
            if ($currentReservations >= $event->getSeats()) {
                $this->addFlash('error', "Désolé, cet événement est complet.");
            } else {
                $entityManager->persist($reservation);
                $entityManager->flush();
                $this->addFlash('success', 'Votre réservation a été confirmée !');
                return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
            }
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
