<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AiService
{
    private string $apiKey;
    private string $apiUrl;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        string $apiKey,
        string $apiUrl,
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->apiKey = $apiKey;
        $this->apiUrl = $apiUrl;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Analyse le motif pour détecter une urgence médicale ou psychologique.
     */
    public function analyzeUrgency(string $motif): bool
    {
        if (empty(trim($motif))) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant médical expert. Analyse le motif de consultation fourni. Réponds UNIQUEMENT par "URGENT" si le motif décrit une situation critique (douleur intense, détresse respiratoire, idées suicidaires, accident grave, etc.) ou "NORMAL" sinon. Ne fournis aucun autre texte.'
                        ],
                        [
                            'role' => 'user',
                            'content' => $motif
                        ]
                    ],
                    'temperature' => 0.1,
                ],
            ]);

            $data = $response->toArray();
            $content = strtoupper(trim($data['choices'][0]['message']['content'] ?? 'NORMAL'));

            return str_contains($content, 'URGENT');
        } catch (\Exception $e) {
            $this->logger->error('Erreur IA Groq (Urgence): ' . $e->getMessage());
            // Fallback simple si l'IA échoue
            return $this->fallbackUrgencyCheck($motif);
        }
    }

    /**
     * Génère une synthèse professionnelle d'une consultation.
     */
    public function generateSummary(string $diagnostic, string $traitement): string
    {
        if (empty($diagnostic) && empty($traitement)) {
            return "Aucune donnée clinique disponible pour la synthèse.";
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant médical. Rédige une synthèse concise et professionnelle (2-3 phrases maximum) de la consultation basée sur le diagnostic et le traitement fournis. Utilise un ton rassurant et formel.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Diagnostic: $diagnostic\nTraitement: $traitement"
                        ]
                    ],
                    'temperature' => 0.3,
                ],
            ]);

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? 'Synthèse indisponible.';
        } catch (\Exception $e) {
            $this->logger->error('Erreur IA Groq (Synthèse): ' . $e->getMessage());
            return "La synthèse IA n'a pas pu être générée pour le moment.";
        }
    }

    /**
     * Suggère un traitement basé sur le diagnostic et les observations.
     */
    public function suggestTreatment(string $diagnostic, string $observation): string
    {
        if (empty(trim($diagnostic))) {
            return "Veuillez saisir au moins un diagnostic pour obtenir une suggestion.";
        }

        try {
            $response = $this->httpClient->request('POST', $this->apiUrl, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => 'Tu es un assistant médical expert en soutien aux praticiens. Propose un protocole de traitement, des conseils d\'hygiène de vie ou des exercices thérapeutiques basés sur le diagnostic et les observations. Utilise un format structuré et professionnel. Rappelle brièvement que le médecin doit valider la prescription.'
                        ],
                        [
                            'role' => 'user',
                            'content' => "Diagnostic: $diagnostic\nObservations: $observation"
                        ]
                    ],
                    'temperature' => 0.4,
                ],
            ]);

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? 'Aucune suggestion générée.';
        } catch (\Exception $e) {
            $this->logger->error('Erreur IA Groq (Suggestion Traitement): ' . $e->getMessage());
            return "Impossible de générer une suggestion pour le moment.";
        }
    }

    /**
     * Détection rudimentaire en cas de panne de l'API.
     */
    private function fallbackUrgencyCheck(string $motif): bool
    {
        $keywords = ['douleur', 'grave', 'urgent', 'suicide', 'sang', 'malaise', 'accident', 'respirer'];
        $motif = mb_strtolower($motif);
        foreach ($keywords as $kw) {
            if (str_contains($motif, $kw)) return true;
        }
        return false;
    }
}
