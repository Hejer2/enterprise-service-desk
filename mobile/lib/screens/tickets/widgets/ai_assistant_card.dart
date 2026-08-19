import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_card.dart';
import '../../../core/widgets/app_toast.dart';
import '../../../core/widgets/skeleton_loader.dart';
import '../../../models/ai_summary.dart';
import '../../../models/ai_ticket_analysis.dart';
import '../../../repositories/ai_repository.dart';
import '../../../services/api_client.dart';

final aiRepositoryProvider =
    Provider((ref) => AiRepository(ref.read(apiClientProvider)));

enum AiActionType {
  analyze,
  summarize,
  draftReply,
  similar,
  knowledge,
  resolution,
}

class AiAssistantCard extends ConsumerStatefulWidget {
  final int ticketId;
  final void Function(String draftText)? onInsertDraft;

  const AiAssistantCard({
    super.key,
    required this.ticketId,
    this.onInsertDraft,
  });

  @override
  ConsumerState<AiAssistantCard> createState() => _AiAssistantCardState();
}

class _AiAssistantCardState extends ConsumerState<AiAssistantCard> {
  AiActionType _selectedAction = AiActionType.analyze;
  bool _isLoading = false;
  String? _loadingMessage;
  String? _errorMessage;

  AiTicketAnalysis? _classification;
  AiSummary? _summary;
  List<Map<String, dynamic>>? _similarTickets;
  Map<String, dynamic>? _resolution;
  Map<String, dynamic>? _knowledgeResult;

  @override
  void initState() {
    super.initState();
    _triggerAction(AiActionType.analyze);
  }

  Future<void> _triggerAction(AiActionType action, {bool forceRefresh = false}) async {
    final repo = ref.read(aiRepositoryProvider);

    if (action == AiActionType.draftReply) {
      setState(() {
        _isLoading = true;
        _loadingMessage = 'Generating AI response draft...';
        _errorMessage = null;
      });
      try {
        final reply = await repo.generateReply(
          widget.ticketId,
          action: 'generate',
          context: 'Provide clear troubleshooting guidance and polite acknowledgement.',
        );
        if (widget.onInsertDraft != null) {
          widget.onInsertDraft!(reply.draft);
        }
        if (mounted) {
          AppToast.showSuccess(
              context, 'AI draft inserted into composer! Review & edit before sending.');
        }
      } catch (e) {
        if (mounted) {
          AppToast.showError(context, 'Failed to generate AI draft: $e');
        }
      } finally {
        if (mounted) setState(() => _isLoading = false);
      }
      return;
    }

    setState(() {
      _selectedAction = action;
      _isLoading = true;
      _errorMessage = null;
      switch (action) {
        case AiActionType.analyze:
          _loadingMessage = 'AI is classifying & analyzing ticket...';
          break;
        case AiActionType.summarize:
          _loadingMessage = 'AI is summarizing ticket details...';
          break;
        case AiActionType.similar:
          _loadingMessage = 'Finding similar historical tickets...';
          break;
        case AiActionType.knowledge:
          _loadingMessage = 'Querying Knowledge Base AI...';
          break;
        case AiActionType.resolution:
          _loadingMessage = 'Generating resolution recommendation...';
          break;
        default:
          _loadingMessage = 'AI is working...';
      }
    });

    try {
      switch (action) {
        case AiActionType.analyze:
          if (_classification == null || forceRefresh) {
            _classification = await repo.classifyTicket(widget.ticketId);
          }
          break;
        case AiActionType.summarize:
          if (_summary == null || forceRefresh) {
            _summary = await repo.summarizeTicket(widget.ticketId);
          }
          break;
        case AiActionType.similar:
          if (_similarTickets == null || forceRefresh) {
            _similarTickets = await repo.findSimilarTickets(widget.ticketId);
          }
          break;
        case AiActionType.knowledge:
          if (_knowledgeResult == null || forceRefresh) {
            _knowledgeResult = await repo.askKnowledge(widget.ticketId);
          }
          break;
        case AiActionType.resolution:
          if (_resolution == null || forceRefresh) {
            _resolution = await repo.recommendResolution(widget.ticketId);
          }
          break;
        case AiActionType.draftReply:
          break;
      }
    } catch (e) {
      _errorMessage = 'AI service temporarily unavailable. Tap retry to reload.';
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(AppSpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Card Header
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Row(
                children: [
                  Icon(Icons.auto_awesome, color: Color(0xFF6366F1), size: 20),
                  SizedBox(width: AppSpacing.sm),
                  Text(
                    'AI Assistant',
                    style: TextStyle(
                      fontSize: 14,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF4F46E5),
                      letterSpacing: 0.3,
                    ),
                  ),
                ],
              ),
              if (_isLoading)
                const SizedBox(
                  width: 16,
                  height: 16,
                  child: CircularProgressIndicator(strokeWidth: 2),
                )
              else
                IconButton(
                  icon: const Icon(Icons.refresh, size: 18, color: AppColors.textSecondary),
                  padding: EdgeInsets.zero,
                  constraints: const BoxConstraints(),
                  tooltip: 'Re-run AI analysis',
                  onPressed: () => _triggerAction(_selectedAction, forceRefresh: true),
                ),
            ],
          ),
          const SizedBox(height: AppSpacing.sm),
          const Divider(color: AppColors.border, height: 1),
          const SizedBox(height: AppSpacing.md),

          // AI Action Buttons
          Wrap(
            spacing: 6,
            runSpacing: 6,
            children: [
              _buildActionButton(
                label: '✨ Analyze Ticket',
                action: AiActionType.analyze,
                icon: Icons.analytics_outlined,
              ),
              _buildActionButton(
                label: '📝 Summarize',
                action: AiActionType.summarize,
                icon: Icons.short_text_rounded,
              ),
              _buildActionButton(
                label: '💬 Draft Reply',
                action: AiActionType.draftReply,
                icon: Icons.rate_review_outlined,
                isDirectAction: true,
              ),
              _buildActionButton(
                label: '🔎 Similar Tickets',
                action: AiActionType.similar,
                icon: Icons.find_in_page_outlined,
              ),
              _buildActionButton(
                label: '📚 Knowledge Base',
                action: AiActionType.knowledge,
                icon: Icons.menu_book_outlined,
              ),
              _buildActionButton(
                label: '🛠 Resolution',
                action: AiActionType.resolution,
                icon: Icons.lightbulb_outline,
              ),
            ],
          ),
          const SizedBox(height: AppSpacing.md),

          // Action Result Container
          if (_isLoading)
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: const Color(0xFFF5F3FF),
                borderRadius: BorderRadius.circular(AppRadius.card),
                border: Border.all(color: const Color(0xFFDDD6FE)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      const SizedBox(
                        width: 14,
                        height: 14,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF6366F1)),
                      ),
                      const SizedBox(width: 8),
                      Flexible(
                        child: Text(
                          _loadingMessage ?? 'AI is processing...',
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(
                            fontSize: 12,
                            fontWeight: FontWeight.w600,
                            color: Color(0xFF6366F1),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: AppSpacing.sm),
                  const SkeletonLoader(width: double.infinity, height: 14),
                  const SizedBox(height: 6),
                  const SkeletonLoader(width: 220, height: 14),
                ],
              ),
            )
          else if (_errorMessage != null)
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: AppColors.dangerLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  const Icon(Icons.error_outline, size: 18, color: AppColors.danger),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _errorMessage!,
                      style: const TextStyle(color: AppColors.danger, fontSize: 12),
                    ),
                  ),
                  TextButton(
                    onPressed: () => _triggerAction(_selectedAction, forceRefresh: true),
                    child: const Text('Retry', style: TextStyle(fontSize: 12)),
                  ),
                ],
              ),
            )
          else
            _buildResultContent(),
        ],
      ),
    );
  }

  Widget _buildActionButton({
    required String label,
    required AiActionType action,
    required IconData icon,
    bool isDirectAction = false,
  }) {
    final isSelected = !isDirectAction && _selectedAction == action;
    return InkWell(
      onTap: () => _triggerAction(action),
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected
              ? const Color(0xFF6366F1)
              : (isDirectAction ? const Color(0xFFEEF2FF) : AppColors.bgApp),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isSelected
                ? const Color(0xFF6366F1)
                : (isDirectAction ? const Color(0xFFC7D2FE) : AppColors.border),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 14,
              color: isSelected
                  ? Colors.white
                  : (isDirectAction ? const Color(0xFF4F46E5) : AppColors.textSecondary),
            ),
            const SizedBox(width: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: isSelected || isDirectAction ? FontWeight.bold : FontWeight.w500,
                color: isSelected
                    ? Colors.white
                    : (isDirectAction ? const Color(0xFF4F46E5) : AppColors.textSecondary),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildResultContent() {
    switch (_selectedAction) {
      case AiActionType.analyze:
        return _buildClassifyContent();
      case AiActionType.summarize:
        return _buildSummarizeContent();
      case AiActionType.similar:
        return _buildSimilarContent();
      case AiActionType.knowledge:
        return _buildKnowledgeContent();
      case AiActionType.resolution:
        return _buildResolutionContent();
      case AiActionType.draftReply:
        return const SizedBox();
    }
  }

  Widget _buildClassifyContent() {
    if (_classification == null) return const Text('No classification analysis available.');
    final c = _classification!;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Wrap(
          spacing: 6,
          runSpacing: 6,
          children: [
            _buildBadge('Category', c.category, const Color(0xFF6366F1)),
            _buildBadge('Priority', c.priority, Colors.orange),
            _buildBadge('Confidence', '${(c.confidence * 100).toInt()}%', Colors.teal),
          ],
        ),
        if (c.suggestedTeam.isNotEmpty) ...[
          const SizedBox(height: 8),
          Row(
            children: [
              const Icon(Icons.people_alt_outlined, size: 14, color: AppColors.primary),
              const SizedBox(width: 4),
              Flexible(
                child: Text(
                  'Suggested Team: ${c.suggestedTeam}',
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                ),
              ),
            ],
          ),
        ],
        if (c.reason.isNotEmpty) ...[
          const SizedBox(height: 6),
          Text(
            c.reason,
            style: const TextStyle(fontSize: 12, color: AppColors.textSecondary, height: 1.3),
          ),
        ],
      ],
    );
  }

  Widget _buildSummarizeContent() {
    if (_summary == null) return const Text('No summary available.');
    final s = _summary!;
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (s.problem.isNotEmpty) ...[
          Text(
            s.problem,
            style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textPrimary, height: 1.3),
          ),
          const SizedBox(height: 8),
        ],
        if (s.details.isNotEmpty) ...[
          const Text('Key Details:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 2),
          ...s.details.map((d) => Padding(
                padding: const EdgeInsets.only(bottom: 2),
                child: Text('• $d', style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
              )),
          const SizedBox(height: 6),
        ],
        if (s.actionsTaken.isNotEmpty) ...[
          const Text('Actions Taken:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 2),
          ...s.actionsTaken.map((a) => Padding(
                padding: const EdgeInsets.only(bottom: 2),
                child: Text('• $a', style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
              )),
          const SizedBox(height: 6),
        ],
        if (s.nextStep.isNotEmpty) ...[
          Container(
            padding: const EdgeInsets.all(6),
            decoration: BoxDecoration(
              color: const Color(0xFFEEF2FF),
              borderRadius: BorderRadius.circular(6),
            ),
            child: Row(
              children: [
                const Icon(Icons.arrow_forward_rounded, size: 14, color: Color(0xFF6366F1)),
                const SizedBox(width: 4),
                Expanded(
                  child: Text('Next Step: ${s.nextStep}',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF4F46E5))),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildSimilarContent() {
    if (_similarTickets == null || _similarTickets!.isEmpty) {
      return const Text('No similar historical tickets found.', style: TextStyle(fontSize: 12, color: AppColors.textSecondary));
    }
    return Column(
      children: _similarTickets!.take(3).map((item) {
        final tckNum = item['ticketNumber']?.toString() ?? 'Ticket';
        final title = item['title']?.toString() ?? 'Issue';
        final score = item['similarityScore'] != null ? '${((item['similarityScore'] as num) * 100).toInt()}% match' : '';
        final res = item['resolutionSummary']?.toString() ?? item['resolution']?.toString();

        return Container(
          margin: const EdgeInsets.only(bottom: 6),
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: AppColors.bgApp,
            borderRadius: BorderRadius.circular(6),
            border: Border.all(color: AppColors.border),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(tckNum, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: Color(0xFF4F46E5))),
                  if (score.isNotEmpty)
                    Text(score, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.teal)),
                ],
              ),
              const SizedBox(height: 2),
              Text(title, style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
              if (res != null && res.isNotEmpty) ...[
                const SizedBox(height: 2),
                Text('Resolution: $res', style: const TextStyle(fontSize: 11, color: AppColors.textSecondary)),
              ],
            ],
          ),
        );
      }).toList(),
    );
  }

  Widget _buildKnowledgeContent() {
    if (_knowledgeResult == null) return const Text('No relevant knowledge base articles found.');
    final answer = _knowledgeResult!['answer']?.toString() ?? _knowledgeResult!['summary']?.toString();
    final articles = _knowledgeResult!['articles'] as List<dynamic>? ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (answer != null && answer.isNotEmpty) ...[
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFFF0FDF4),
              borderRadius: BorderRadius.circular(6),
              border: Border.all(color: const Color(0xFFBBF7D0)),
            ),
            child: Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Icon(Icons.auto_awesome, size: 14, color: Colors.green),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(answer, style: const TextStyle(fontSize: 12, color: AppColors.textPrimary, height: 1.3)),
                ),
              ],
            ),
          ),
          const SizedBox(height: 8),
        ],
        if (articles.isNotEmpty) ...[
          const Text('Published KB Articles:', style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey)),
          const SizedBox(height: 4),
          ...articles.take(3).map((art) => Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Row(
                  children: [
                    const Icon(Icons.menu_book_outlined, size: 14, color: Color(0xFF6366F1)),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(art['title']?.toString() ?? 'KB Article',
                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF4F46E5))),
                    ),
                  ],
                ),
              )),
        ],
      ],
    );
  }

  Widget _buildResolutionContent() {
    if (_resolution == null) return const Text('No resolution recommendation available.');
    final action = _resolution!['recommendedAction']?.toString() ?? _resolution!['recommendation']?.toString() ?? '';
    final steps = _resolution!['suggestedSteps'] as List<dynamic>? ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (action.isNotEmpty) ...[
          Text(action, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
          const SizedBox(height: 6),
        ],
        if (steps.isNotEmpty)
          ...steps.map((step) => Padding(
                padding: const EdgeInsets.only(bottom: 4),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Icon(Icons.check_circle_outline, size: 14, color: Colors.teal),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(step.toString(), style: const TextStyle(fontSize: 12, color: AppColors.textSecondary)),
                    ),
                  ],
                ),
              )),
      ],
    );
  }

  Widget _buildBadge(String label, String value, Color color) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(
        '$label: $value',
        style: TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: color),
      ),
    );
  }
}
