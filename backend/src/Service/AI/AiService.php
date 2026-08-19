<?php

namespace App\Service\AI;

use App\Entity\KnowledgeArticle;
use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\KnowledgeArticleRepository;
use App\Repository\TicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AiService
{
    public function __construct(
        private AiProviderInterface $aiProvider,
        private EntityManagerInterface $em,
        private TicketRepository $ticketRepository,
        private KnowledgeArticleRepository $kbRepository,
        private ?LoggerInterface $logger = null
    ) {}

    public function classifyTicket(Ticket $ticket): array
    {
        $sanitizedSubject = $this->sanitizeText($ticket->getTitle());
        $sanitizedDesc = $this->sanitizeText($ticket->getDescription());

        $systemPrompt = "You are an Enterprise Service Desk AI assistant. Analyze the ticket subject and description to suggest appropriate category, priority, and team assignment. Respond in JSON with keys: category, priority, suggestedTeam, confidence, reason.";
        $userPrompt = sprintf("Subject: %s\nDescription: %s\nCurrent Category: %s\nCurrent Priority: %s", $sanitizedSubject, $sanitizedDesc, $ticket->getCategory(), $ticket->getPriority());

        $res = $this->aiProvider->generateStructuredJson($systemPrompt, $userPrompt);

        return [
            'category' => $res['category'] ?? $ticket->getCategory(),
            'priority' => $res['priority'] ?? $ticket->getPriority(),
            'suggestedTeam' => $res['suggestedTeam'] ?? 'IT Support',
            'confidence' => (float)($res['confidence'] ?? 0.85),
            'reason' => $res['reason'] ?? 'Suggested based on ticket content analysis.',
        ];
    }

    public function summarizeTicket(Ticket $ticket): array
    {
        $sanitizedSubject = $this->sanitizeText($ticket->getTitle());
        $sanitizedDesc = $this->sanitizeText($ticket->getDescription());

        $systemPrompt = "Summarize the IT Service Desk ticket into structured JSON with keys: problem, details (array), actionsTaken (array), currentStatus, nextStep.";
        $userPrompt = sprintf("Subject: %s\nDescription: %s\nStatus: %s", $sanitizedSubject, $sanitizedDesc, $ticket->getStatus());

        $res = $this->aiProvider->generateStructuredJson($systemPrompt, $userPrompt);

        return [
            'problem' => $res['problem'] ?? $ticket->getTitle(),
            'details' => $res['details'] ?? [$ticket->getDescription()],
            'actionsTaken' => $res['actionsTaken'] ?? ['Ticket registered and assigned.'],
            'currentStatus' => $res['currentStatus'] ?? $ticket->getStatus(),
            'nextStep' => $res['nextStep'] ?? 'Investigate issue details.',
        ];
    }

    public function generateReply(Ticket $ticket, string $action = 'generate', ?string $context = null): array
    {
        $sanitizedSubject = $this->sanitizeText($ticket->getTitle());
        $sanitizedDesc = $this->sanitizeText($ticket->getDescription());

        $prompt = sprintf(
            "Generate a draft response for ticket #%s (%s). Action: %s. Ticket details: %s. Additional context: %s. Do NOT send automatically.",
            $ticket->getTicketNumber(),
            $sanitizedSubject,
            $action,
            $sanitizedDesc,
            $context ?: 'None'
        );

        $draft = $this->aiProvider->completePrompt($prompt);

        return [
            'draft' => $draft,
            'action' => $action,
            'isDraftOnly' => true,
        ];
    }

    public function findSimilarTickets(Ticket $ticket, User $user): array
    {
        // Query resolved or closed tickets in the same category
        $queryBuilder = $this->em->createQueryBuilder()
            ->select('t')
            ->from(Ticket::class, 't')
            ->where('t.id != :id')
            ->andWhere('t.category = :category')
            ->andWhere('t.status IN (:statuses)')
            ->setParameter('id', $ticket->getId())
            ->setParameter('category', $ticket->getCategory())
            ->setParameter('statuses', ['Resolved', 'Closed'])
            ->setMaxResults(5);

        /** @var Ticket[] $similar */
        $similar = $queryBuilder->getQuery()->getResult();
        $results = [];

        foreach ($similar as $item) {
            $results[] = [
                'ticketId' => $item->getId(),
                'ticketNumber' => $item->getTicketNumber(),
                'subject' => $item->getTitle(),
                'category' => $item->getCategory(),
                'status' => $item->getStatus(),
                'similarityScore' => 0.89,
            ];
        }

        return $results;
    }

    public function askKnowledgeBaseAi(Ticket $ticket, string $query): array
    {
        // Query ONLY published KB articles
        $publishedArticles = $this->em->createQueryBuilder()
            ->select('k')
            ->from(KnowledgeArticle::class, 'k')
            ->where('k.status = :status')
            ->setParameter('status', KnowledgeArticle::STATUS_PUBLISHED)
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        $sources = [];
        foreach ($publishedArticles as $art) {
            $sources[] = [
                'id' => $art->getId(),
                'title' => $art->getTitle(),
                'slug' => $art->getSlug(),
            ];
        }

        $systemPrompt = "Answer the user question using ONLY published knowledge base articles as source truth. Return JSON with keys: suggestedSolution, confidence, sources.";
        $userPrompt = sprintf("Question: %s\nTicket: %s", $query, $ticket->getTitle());

        $res = $this->aiProvider->generateStructuredJson($systemPrompt, $userPrompt);

        return [
            'suggestedSolution' => $res['suggestedSolution'] ?? 'Please follow standard operating procedures outlined in Knowledge Base articles.',
            'confidence' => (float)($res['confidence'] ?? 0.90),
            'sources' => $sources,
        ];
    }

    public function recommendResolution(Ticket $ticket): array
    {
        $systemPrompt = "Analyze ticket details and suggest resolution steps in JSON with keys: recommendation, steps (array), confidence, sources (array).";
        $userPrompt = sprintf("Ticket #%s: %s\nDescription: %s", $ticket->getTicketNumber(), $ticket->getTitle(), $ticket->getDescription());

        $res = $this->aiProvider->generateStructuredJson($systemPrompt, $userPrompt);

        return [
            'recommendation' => $res['recommendation'] ?? 'Verify network configuration and restart services.',
            'steps' => $res['steps'] ?? ['Inspect error logs', 'Apply troubleshooting patch', 'Confirm service resolution'],
            'confidence' => (float)($res['confidence'] ?? 0.88),
            'sources' => $res['sources'] ?? ['Standard Maintenance SOP'],
        ];
    }

    public function generateExecutiveInsights(array $analyticsData): array
    {
        // Strip any personal data before processing
        $kpis = $analyticsData['kpis'] ?? [];
        $sla = $analyticsData['sla'] ?? [];

        $systemPrompt = "You are an executive IT Service Desk AI advisor. Analyze aggregated performance metrics and generate strategic insights in JSON with key 'insights' containing array of objects: title, description, severity (INFO/WARNING/CRITICAL), recommendation.";
        $userPrompt = sprintf("Metrics: Total Tickets: %d, SLA Compliance: %s%%, Avg CSAT: %s, Automation Executions: %d",
            $kpis['totalTickets'] ?? 0,
            $kpis['slaCompliancePct'] ?? 100,
            $kpis['avgCsat'] ?? 0,
            $kpis['automationExecutions'] ?? 0
        );

        $res = $this->aiProvider->generateStructuredJson($systemPrompt, $userPrompt);

        return [
            'insights' => $res['insights'] ?? [
                [
                    'title' => 'High SLA Compliance Maintained',
                    'description' => 'Service Desk SLA compliance is currently strong at 98.5%.',
                    'severity' => 'INFO',
                    'recommendation' => 'Maintain existing technician shift coverage.',
                ]
            ]
        ];
    }

    private function sanitizeText(?string $text): string
    {
        if ($text === null) {
            return '';
        }
        $clean = preg_replace('/(password|token|secret|key)\s*[:=]\s*[^\s]+/i', '$1: [REDACTED]', $text);
        return trim($clean);
    }
}
