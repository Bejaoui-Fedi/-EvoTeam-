<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class GeminiAiService
{
    private string $apiKey;

    public function __construct(private HttpClientInterface $client)
    {
        // On recupere la cle ou on met un fallback
        $this->apiKey = $_ENV['GEMINI_API_KEY'] ?? 'dummy_key';
    }

    public function analyzeSentiment(string $text): string
    {
        if ($this->apiKey === 'dummy_key' || empty($this->apiKey)) {
            return 'Neutre';
        }

        $prompt = "Analyse le sentiment de cet avis client sur un événement. Tu dois répondre EXACTEMENT et UNIQUEMENT par un seul mot parmi cette liste stricte : Positif, Neutre, Négatif. Aucun autre mot, pas de point.\n\nVoici le texte : \"$text\"";

        $result = $this->callGemini($prompt);
        
        // Nettoyage au cas ou l'IA rajoute un point ou un saut de ligne
        $cleaned = trim(str_replace('.', '', $result));
        if (in_array($cleaned, ['Positif', 'Neutre', 'Négatif'])) {
            return $cleaned;
        }

        return 'Neutre';
    }

    public function generateEventDescription(string $title): string
    {
        if ($this->apiKey === 'dummy_key' || empty($this->apiKey)) {
            return "Une description fantastique générée automatiquement par l'IA pour l'événement : $title. (Modifiez la clé dans votre fichier .env pour activer l'IA).";
        }

        $prompt = "Tu es un expert en marketing d'événements. Rédige une description très accrocheuse, professionnelle et chaleureuse (environ 3 ou 4 phrases maximum) pour un événement qui s'appelle : \"$title\". Ne mets aucun titre dans ta réponse, juste le paragraphe de texte.";

        return $this->callGemini($prompt);
    }

    private function callGemini(string $prompt): string
    {
        try {
            $response = $this->client->request('POST', 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent', [
                'query' => [
                    'key' => $this->apiKey,
                ],
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            ]);

            $content = $response->toArray();
            
            if (isset($content['candidates'][0]['content']['parts'][0]['text'])) {
                return trim($content['candidates'][0]['content']['parts'][0]['text']);
            }

            return 'Erreur IA.';
        } catch (\Exception $e) {
            return 'Service IA temporairement indisponible.';
        }
    }
}
