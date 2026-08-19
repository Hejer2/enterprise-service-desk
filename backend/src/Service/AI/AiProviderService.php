<?php

namespace App\Service\AI;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AiProviderService implements AiProviderInterface
{
    private string $provider;
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct(
        private ?HttpClientInterface $httpClient = null,
        private ?LoggerInterface $logger = null
    ) {
        $this->provider = $_ENV['AI_PROVIDER'] ?? $_SERVER['AI_PROVIDER'] ?? 'mock';
        $this->apiKey = $_ENV['AI_API_KEY'] ?? $_SERVER['AI_API_KEY'] ?? '';
        $this->model = $_ENV['AI_MODEL'] ?? $_SERVER['AI_MODEL'] ?? 'gpt-4o-mini';
        $this->baseUrl = $_ENV['AI_BASE_URL'] ?? $_SERVER['AI_BASE_URL'] ?? 'https://api.openai.com/v1';
    }

    public function completePrompt(string $prompt, array $options = []): string
    {
        if (empty($this->apiKey) || $this->provider === 'mock') {
            return $this->getMockCompletion($prompt);
        }

        try {
            if (!$this->httpClient) {
                return $this->getMockCompletion($prompt);
            }

            $response = $this->httpClient->request('POST', rtrim($this->baseUrl, '/') . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                    'temperature' => 0.3,
                ],
                'timeout' => 8,
            ]);

            $data = $response->toArray();
            return $data['choices'][0]['message']['content'] ?? $this->getMockCompletion($prompt);
        } catch (\Throwable $e) {
            $this->logger?->warning('AI Provider request failed: ' . $e->getMessage());
            return $this->getMockCompletion($prompt);
        }
    }

    public function generateStructuredJson(string $systemPrompt, string $userPrompt): array
    {
        $prompt = sprintf("System: %s\nUser: %s\nRespond strictly with valid JSON.", $systemPrompt, $userPrompt);
        $raw = $this->completePrompt($prompt);

        // Clean markdown codeblocks if present
        $clean = preg_replace('/```(?:json)?\s*(.*?)\s*```/s', '$1', trim($raw));
        $decoded = json_decode($clean, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        return $this->getMockStructuredJson($userPrompt);
    }

    private function getMockCompletion(string $prompt): string
    {
        if (str_contains(strtolower($prompt), 'reply')) {
            return "Thank you for reaching out to the Enterprise Service Desk. We have reviewed your request and our technical team is currently investigating the issue. We will update you as soon as progress is made.";
        }
        return "AI Analysis Completed successfully. The ticket is currently being evaluated based on historical resolution patterns.";
    }

    private function getMockStructuredJson(string $userPrompt): array
    {
        if (str_contains(strtolower($userPrompt), 'classify')) {
            return [
                'category' => 'IT Support',
                'priority' => 'High',
                'suggestedTeam' => 'IT Infrastructure Team',
                'confidence' => 0.92,
                'reason' => 'Ticket mentions hardware connectivity and network errors.',
            ];
        }

        if (str_contains(strtolower($userPrompt), 'summarize')) {
            return [
                'problem' => 'User experiencing system connectivity degradation.',
                'details' => ['Network timeout errors', 'Occurs during peak usage hours'],
                'actionsTaken' => ['Initial diagnosis performed', 'Workload load-balanced'],
                'currentStatus' => 'Under active technician investigation',
                'nextStep' => 'Verify network switch configuration and update firmware',
            ];
        }

        if (str_contains(strtolower($userPrompt), 'resolution')) {
            return [
                'recommendation' => 'Reset network adapter settings and clear local DNS cache.',
                'steps' => [
                    'Open Command Prompt as Administrator.',
                    'Execute ipconfig /flushdns and netsh winsock reset.',
                    'Reboot system and re-test connection.',
                ],
                'confidence' => 0.88,
                'sources' => ['KB-101: Network Troubleshooting Guide'],
            ];
        }

        if (str_contains(strtolower($userPrompt), 'executive')) {
            return [
                'insights' => [
                    [
                        'title' => 'High SLA Compliance Maintained',
                        'description' => 'SLA compliance is currently at 98.5% across all IT Support tickets.',
                        'severity' => 'INFO',
                        'recommendation' => 'Maintain current technician shift coverage.',
                    ],
                    [
                        'title' => 'Machine Maintenance Reopen Pattern',
                        'description' => 'Slight increase in reopened tickets for shop floor machinery.',
                        'severity' => 'WARNING',
                        'recommendation' => 'Schedule preventive maintenance check for Line B equipment.',
                    ]
                ]
            ];
        }

        return [
            'status' => 'success',
            'message' => 'AI structured response fallback',
        ];
    }
}
