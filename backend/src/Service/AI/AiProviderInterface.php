<?php

namespace App\Service\AI;

interface AiProviderInterface
{
    /**
     * Complete a prompt text and return string response.
     */
    public function completePrompt(string $prompt, array $options = []): string;

    /**
     * Generate structured JSON from system & user prompt.
     */
    public function generateStructuredJson(string $systemPrompt, string $userPrompt): array;
}
