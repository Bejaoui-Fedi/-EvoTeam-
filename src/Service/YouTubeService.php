<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class YouTubeService
{
    private string $apiKey;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(string $apiKey, HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->apiKey = $apiKey;
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Searches for a video on YouTube and returns the first result's watch URL.
     *
     * @param string $query The search query (e.g., "Push-up correct form tutorial")
     * @return string|null Full YouTube video URL or null if not found/error.
     */
    public function searchVideoUrl(string $query): ?string
    {
        if (empty($this->apiKey) || $this->apiKey === 'your_youtube_api_key') {
            $this->logger->error('YouTube API Key (YOUTUBE_API_KEY) is missing or not configured.');
            return null;
        }

        try {
            $searchQuery = $query . ' wellness exercise tutorial';
            $this->logger->info('Searching YouTube for: ' . $searchQuery);

            $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/search', [
                'query' => [
                    'part' => 'snippet',
                    'maxResults' => 1,
                    'q' => $searchQuery,
                    'type' => 'video',
                    'key' => $this->apiKey,
                ],
            ]);

            $statusCode = $response->getStatusCode();
            if ($statusCode !== 200) {
                $this->logger->error('YouTube API Error [' . $statusCode . ']: ' . $response->getContent(false));
                return null;
            }

            $data = $response->toArray();
            $items = $data['items'] ?? [];

            if (!empty($items)) {
                $videoId = $items[0]['id']['videoId'] ?? null;
                if ($videoId) {
                    return 'https://www.youtube.com/watch?v=' . $videoId;
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Exception in YouTubeService: ' . $e->getMessage());
        }

        return null;
    }
}
