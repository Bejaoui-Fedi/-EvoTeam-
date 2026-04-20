<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ChatbotController extends AbstractController
{
    private string $geminiKey;
    private string $groqKey;
    private string $systemPrompt;

    public function __construct(private HttpClientInterface $httpClient)
    {
        // Charger les clés API depuis Chatbot/.env
        $envPath = dirname(__DIR__, 2) . '/Chatbot/.env';
        if (file_exists($envPath)) {
            foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
                [$key, $val] = explode('=', $line, 2);
                match (trim($key)) {
                    'GEMINI_API_KEY' => $this->geminiKey = trim($val),
                    'GROQ_API_KEY'   => $this->groqKey   = trim($val),
                    default          => null,
                };
            }
        }

        $this->systemPrompt = "Tu es l'assistant IA officiel de la plateforme Evolia, spécialisée dans la psychologie et le développement personnel. Ton rôle est d'accompagner les utilisateurs avec empathie, professionnalisme et bienveillance. Tes domaines d'expertise sont : la gestion du stress, la confiance en soi, la motivation, les relations humaines et le bien-être mental.";
    }

    #[Route('/api/chat', name: 'api_chat', methods: ['POST'])]
    public function chat(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userMessage = $data['message'] ?? '';

        if (empty($userMessage)) {
            return new JsonResponse(['error' => 'No message provided'], 400);
        }

        // Contexte utilisateur
        $contextStr = '';
        $user = $this->getUser();
        if ($user && method_exists($user, 'getNom')) {
            $name = $user->getNom() ?? 'Utilisateur';
            $role = $user->getRole() ?? 'Patient';
            $contextStr = "\nTu parles à {$name}, dont le rôle sur la plateforme est {$role}.";
        }

        $fullPrompt = $this->systemPrompt . $contextStr;

        // Essai Gemini, puis Groq en fallback
        try {
            $reply = $this->callGemini($userMessage, $fullPrompt);
        } catch (\Throwable $e1) {
            try {
                $reply = $this->callGroq($userMessage, $fullPrompt);
            } catch (\Throwable $e2) {
                return new JsonResponse([
                    'error'  => 'Les deux IA sont indisponibles.',
                    'gemini' => $e1->getMessage(),
                    'groq'   => $e2->getMessage(),
                ], 503);
            }
        }

        return new JsonResponse(['response' => $reply]);
    }

    private function callGemini(string $message, string $systemPrompt): string
    {
        if (empty($this->geminiKey)) throw new \RuntimeException('Clé Gemini manquante.');

        $response = $this->httpClient->request('POST',
            "https://generativelanguage.googleapis.com/v1/models/gemini-2.0-flash:generateContent?key={$this->geminiKey}",
            [
                'json' => [
                    'contents' => [[
                        'parts' => [['text' => $systemPrompt . "\n\nUtilisateur: " . $message]]
                    ]]
                ],
            ]
        );

        $body = $response->toArray();
        return $body['candidates'][0]['content']['parts'][0]['text'];
    }

    private function callGroq(string $message, string $systemPrompt): string
    {
        if (empty($this->groqKey)) throw new \RuntimeException('Clé Groq manquante.');

        $response = $this->httpClient->request('POST',
            'https://api.groq.com/openai/v1/chat/completions',
            [
                'headers' => ['Authorization' => "Bearer {$this->groqKey}"],
                'json'    => [
                    'model'    => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user',   'content' => $message],
                    ],
                ],
            ]
        );

        $body = $response->toArray();
        return $body['choices'][0]['message']['content'];
    }
}
