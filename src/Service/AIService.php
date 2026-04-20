<?php

namespace App\Service;

use App\Entity\Exercise;
use App\Entity\Objective;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class AIService
{
    private string $groqKey;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(string $groqKey, HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->groqKey = $groqKey;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Generates a list of exercises for a given objective using Groq AI (Llama 3).
     *
     * @return Exercise[]
     */
    public function generateExercisesForObjective(Objective $objective): array
    {
        if (empty($this->groqKey) || str_contains($this->groqKey, 'your_')) {
            throw new \Exception("La clé API Groq n'est pas configurée.");
        }

        $systemPrompt = "You are a professional fitness and mental health coach. " .
            "Given an objective, generate a JSON array of 3-5 exercises. " .
            "Each object MUST have: \"title\", \"description\", \"type\", \"durationMinutes\" (int), \"difficulty\", \"steps\".\n" .
            "IMPORTANT constraints for database compatibility:\n" .
            "- \"type\" MUST be one of: [\"respiration\", \"journaling\", \"meditation\", \"cbt\", \"challenge\", \"relaxation\"]\n" .
            "- \"difficulty\" MUST be one of: [\"debutant\", \"moyen\", \"avance\"]\n" .
            "Respond ONLY with the RAW JSON array. No explanations, no backticks.";

        $userPrompt = sprintf(
            "Objective: %s\nDescription: %s\nLevel: %s",
            $objective->getTitle(),
            $objective->getDescription(),
            $objective->getLevel()
        );

        try {
            $response = $this->httpClient->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->groqKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.7,
                ],
            ]);

            $data = $response->toArray();
            $content = $data['choices'][0]['message']['content'] ?? '';
            
            // Clean markdown if present
            $content = preg_replace('/^```json\s*|```$/m', '', $content);
            $exerciseData = json_decode($content, true);

            if (!is_array($exerciseData)) {
                $this->logger->error("AI Response parsing failed: " . $content);
                return [];
            }

            $exercises = [];
            foreach ($exerciseData as $exJson) {
                $exercise = new Exercise();
                $exercise->setTitle($exJson['title'] ?? 'Nouvel Exercice');
                $exercise->setDescription($exJson['description'] ?? '');
                $exercise->setType($this->mapToValidType($exJson['type'] ?? 'relaxation'));
                $exercise->setDurationMinutes((int)($exJson['durationMinutes'] ?? 15));
                $exercise->setDifficulty($this->mapToValidDifficulty($exJson['difficulty'] ?? 'moyen'));
                
                // Handle steps (could be array or string)
                $steps = $exJson['steps'] ?? '';
                if (is_array($steps)) {
                    $steps = implode("\n", array_map(fn($s) => "- " . $s, $steps));
                }
                $exercise->setSteps($steps);
                
                $exercise->setIsPublished(1);
                $exercise->setObjective($objective);
                
                $exercises[] = $exercise;
            }

            return $exercises;

        } catch (\Exception $e) {
            $this->logger->error("AIService Error: " . $e->getMessage());
            throw $e;
        }
    }

    private function mapToValidType(string $type): string
    {
        $allowed = ["respiration", "journaling", "meditation", "cbt", "challenge", "relaxation"];
        $type = strtolower($type);
        return in_array($type, $allowed) ? $type : "relaxation";
    }

    private function mapToValidDifficulty(string $diff): string
    {
        $allowed = ["debutant", "moyen", "avance"];
        $diff = strtolower($diff);
        return in_array($diff, $allowed) ? $diff : "moyen";
    }
}
