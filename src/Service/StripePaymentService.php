<?php

namespace App\Service;

use App\Entity\Participation;
use Stripe\StripeClient;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripePaymentService
{
    private StripeClient $stripe;

    public function __construct(private readonly UrlGeneratorInterface $router)
    {
        $secretKey = $_ENV['STRIPE_SECRET_KEY'] ?? 'sk_test_123456789'; // A remplacer dans .env
        $this->stripe = new StripeClient($secretKey);
    }

    public function createCheckoutSession(Participation $participation): string
    {
        $event = $participation->getEvent();
        $fee = (float) $event?->getFee();

        // Le montant chez Stripe est en centimes (1 EUR = 100 centimes)
        $amountInCents = (int) ($fee * 100);
        
        // Sécurité minimale si le prix n'a pas été bien défini
        if ($amountInCents <= 0) {
            $amountInCents = 100;
        }

        $session = $this->stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Billet: ' . $event?->getName(),
                        'description' => 'Frais de participation pour ' . $participation->getSeats() . ' place(s).',
                    ],
                    'unit_amount' => $amountInCents,
                ],
                // On fixe la quantite a 1 ici car on multiplie les frais globaux plus tard si besoin, 
                // mais puisque unit_amount c'est par item, la quantité c'est le nombre de places.
                'quantity' => $participation->getSeats(),
            ]],
            'mode' => 'payment',
            'success_url' => $this->router->generate('app_participation_payment_success', ['id' => $participation->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->router->generate('app_participation_payment_cancel', ['id' => $participation->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'client_reference_id' => (string) $participation->getId(),
        ]);

        return $session->url;
    }
}
