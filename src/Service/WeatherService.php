<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class WeatherService
{
    private HttpClientInterface $httpClient;
    private string $apiKey;

    public function __construct(HttpClientInterface $httpClient, string $weatherApiKey)
    {
        $this->httpClient = $httpClient;
        $this->apiKey = $weatherApiKey;
    }

    /**
     * Récupère les infos météo pour une ville donnée (défaut: Tunis).
     */
    public function getWeather(string $city = "Tunis"): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                'https://api.openweathermap.org/data/2.5/weather',
                [
                    'query' => [
                        'q' => $city,
                        'units' => 'metric',
                        'lang' => 'fr',
                        'appid' => $this->apiKey,
                    ],
                ]
            );

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                return [
                    'temp' => round($data['main']['temp'], 1),
                    'description' => ucfirst($data['weather'][0]['description']),
                    'icon' => $data['weather'][0]['icon'],
                    'city' => $data['name']
                ];
            }
        } catch (\Exception $e) {
            // Silently fail if API is down
        }

        return ['temp' => '--', 'description' => 'Météo indisponible', 'icon' => null, 'city' => $city];
    }
}
