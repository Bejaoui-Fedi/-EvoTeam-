<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class HabitAiService
{
    private $httpClient;
    private $masterApiKey;
    private $baseUrl;

    public function __construct(
        HttpClientInterface $httpClient,
        #[Autowire('%env(HABIT_AI_KEY)%')] string $masterApiKey,
        #[Autowire('%env(HABIT_AI_BASE_URL)%')] string $baseUrl
    ) {
        $this->httpClient = $httpClient;
        $this->masterApiKey = $masterApiKey;
        $this->baseUrl = $baseUrl;
    }

    private function request(string $method, string $endpoint, array $options = [], ?string $token = null): array
    {
        // Treat empty string or null as using the Master API Key
        $apiKey = (!empty(trim($token ?? ''))) ? $token : $this->masterApiKey;
        
        $defaultOptions = [
            'headers' => [
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ],
        ];

        $options = array_merge_recursive($defaultOptions, $options);

        try {
            $response = $this->httpClient->request($method, $this->baseUrl . $endpoint, $options);
            $statusCode = $response->getStatusCode();
            
            if ($statusCode >= 200 && $statusCode < 300) {
                $content = $response->getContent(false);
                return !empty($content) ? $response->toArray() : ['success' => true];
            }
            
            // Throw exception for non-2xx status to trigger fallback logic
            throw new \RuntimeException('API returned status ' . $statusCode);
        } catch (\Exception $e) {
            // Re-throw if it's already a RuntimeException we just created, 
            // otherwise wrap in one.
            if ($e instanceof \RuntimeException) throw $e;
            throw new \RuntimeException($e->getMessage());
        }
    }

    public function getProfile(?string $token = null): array
    {
        return $this->request('GET', '/profile', [], $token);
    }

    public function getSteps(int $days = 7, ?string $token = null): array
    {
        return $this->request('GET', '/steps', ['query' => ['days' => $days]], $token);
    }

    public function getWeight(int $days = 30, ?string $token = null): array
    {
        return $this->request('GET', '/weight', ['query' => ['days' => $days]], $token);
    }

    public function getDailyNutrition(?string $date = null, ?string $token = null): array
    {
        $query = $date ? ['date' => $date] : [];
        return $this->request('GET', '/nutrition/daily', ['query' => $query], $token);
    }

    public function getMeals(?string $date = null, ?string $token = null): array
    {
        $query = $date ? ['date' => $date] : [];
        return $this->request('GET', '/meals', ['query' => $query], $token);
    }

    public function logSteps(int $steps, string $date, ?string $token = null): array
    {
        return $this->request('POST', '/steps', [
            'json' => ['steps' => $steps, 'date' => $date]
        ], $token);
    }

    public function logWeight(float $weight, string $date, ?string $token = null): array
    {
        return $this->request('POST', '/weight', [
            'json' => ['weightInKg' => $weight, 'date' => $date]
        ], $token);
    }

    public function logWater(int $amount, string $date, ?string $token = null): array
    {
        return $this->request('POST', '/water', [
            'json' => ['amount' => $amount, 'date' => $date]
        ], $token);
    }

    public function logCalories(int $calories, string $date, ?string $token = null): array
    {
        return $this->logMeal([
            'mealName' => 'Manual Entry',
            'calories' => $calories,
            'mealType' => 'snack'
        ], $token);
    }

    public function logNutrition(array $data, string $date, ?string $token = null): array
    {
        $payload = array_merge([
            'mealName' => 'Nutrition Log',
            'mealType' => 'snack'
        ], $data);
        
        return $this->logMeal($payload, $token);
    }

    public function logMeal(array $data, ?string $token = null): array
    {
        return $this->request('POST', '/meals', [
            'json' => $data
        ], $token);
    }

    public function logMealDescription(string $description, string $date, ?string $token = null): array
    {
        // Step 1: Analyze the description
        $analysis = $this->analyzeMealDescription($description, 'snack', $token);
        
        if (isset($analysis['error'])) return $analysis;

        // Step 2: Extract data and log as a meal. Use robust mapping for AI analysis results.
        $extract = function($keys) use ($analysis) {
            foreach ($keys as $k) {
                if (isset($analysis[$k]) && is_numeric($analysis[$k])) return $analysis[$k];
            }
            return 0;
        };

        $mealData = [
            'mealName' => $description,
            'calories' => (int)$extract(['calories', 'total_calories', 'totalCalories', 'kcal']),
            'protein' => (int)$extract(['protein', 'total_protein', 'totalProtein']),
            'carbs' => (int)$extract(['carbs', 'total_carbs', 'totalCarbs']),
            'fat' => (int)$extract(['fat', 'total_fat', 'totalFat']),
            'fiber' => (int)$extract(['fiber', 'total_fiber', 'totalFiber']),
            'sugar' => (int)$extract(['sugar', 'total_sugar', 'totalSugar']),
            'sodium' => (int)$extract(['sodium', 'total_sodium', 'totalSodium']),
            'date' => $date, // Pass the date to ensure it lands on the correct day in the dashboard
            'mealType' => $analysis['mealType'] ?? 'snack'
        ];

        return $this->logMeal($mealData, $token);
    }

    public function logJournalEntry(string $content, string $mood, ?string $token = null): array
    {
        return $this->request('POST', '/journal', [
            'json' => ['content' => $content, 'mood' => $mood]
        ], $token);
    }

    public function analyzeMealDescription(string $description, string $mealType = 'lunch', ?string $token = null): array
    {
        return $this->request('POST', '/analyze/meal-description', [
            'json' => ['description' => $description, 'mealType' => $mealType]
        ], $token);
    }

    public function sendMessage(string $coach, string $message, array $history = [], ?string $token = null): array
    {
        $endpoint = match ($coach) {
            'eating' => '/coaches/eating',
            'mindfulness' => '/coaches/mindfulness',
            'meditation' => '/coaches/meditation',
            default => '/coaches/mindfulness',
        };

        $json = ['message' => $message];
        if (!empty($history) && $coach === 'eating') {
            $json['conversationHistory'] = $history;
        }

        return $this->request('POST', $endpoint, ['json' => $json], $token);
    }
}
