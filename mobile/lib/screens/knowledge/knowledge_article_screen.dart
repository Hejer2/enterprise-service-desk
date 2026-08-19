import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_toast.dart';
import '../../core/widgets/error_state.dart';
import '../../core/widgets/skeleton_loader.dart';
import '../../models/knowledge_article.dart';
import '../tickets/ticket_list_screen.dart';
import 'knowledge_screen.dart';

final articleDetailsProvider = FutureProvider.family
    .autoDispose<KnowledgeArticle, String>((ref, slug) async {
  final repo = ref.read(knowledgeRepositoryProvider);
  return repo.getArticleDetails(slug);
});

class KnowledgeArticleScreen extends ConsumerStatefulWidget {
  final String articleSlug;

  const KnowledgeArticleScreen({super.key, required this.articleSlug});

  @override
  ConsumerState<KnowledgeArticleScreen> createState() =>
      _KnowledgeArticleScreenState();
}

class _KnowledgeArticleScreenState
    extends ConsumerState<KnowledgeArticleScreen> {
  bool _submittedFeedback = false;

  Future<void> _submitFeedback(int articleId, bool helpful) async {
    try {
      final repo = ref.read(knowledgeRepositoryProvider);
      await repo.submitFeedback(articleId, helpful);
      setState(() {
        _submittedFeedback = true;
      });
      if (mounted) {
        AppToast.showSuccess(context, 'Thank you for your feedback!');
      }
    } catch (e) {
      if (mounted) {
        AppToast.showError(context, 'Failed to submit feedback');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final articleAsync = ref.watch(articleDetailsProvider(widget.articleSlug));

    return Scaffold(
      appBar: AppBar(
        title: const Text('Article Details'),
      ),
      body: articleAsync.when(
        loading: () => const Padding(
          padding: EdgeInsets.all(16),
          child: SkeletonLoader(width: double.infinity, height: 140),
        ),
        error: (err, stack) => ErrorState(
          title: 'Failed to load article',
          message: err.toString(),
          onRetry: () =>
              ref.invalidate(articleDetailsProvider(widget.articleSlug)),
        ),
        data: (article) {
          final dateFormat = DateFormat('dd MMM yyyy');
          final formattedDate = article.publishedAt != null
              ? dateFormat.format(article.publishedAt!)
              : '';

          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Category & Meta Tag
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        article.categoryName.toUpperCase(),
                        style: const TextStyle(
                          color: AppColors.primary,
                          fontWeight: FontWeight.bold,
                          fontSize: 11,
                        ),
                      ),
                    ),
                    Text(
                      formattedDate,
                      style: const TextStyle(fontSize: 12, color: Colors.grey),
                    ),
                  ],
                ),
                const SizedBox(height: 12),

                // Article Title
                Semantics(
                  header: true,
                  label: article.title,
                  child: Text(
                    article.title,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Article Content
                AppCard(
                  padding: const EdgeInsets.all(16),
                  child: Text(
                    article.content ?? article.excerpt ?? '',
                    style: const TextStyle(
                      fontSize: 15,
                      height: 1.6,
                      color: AppColors.textPrimary,
                    ),
                  ),
                ),
                const SizedBox(height: 20),

                // Helpful Feedback Card
                AppCard(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      const Text(
                        'Was this article helpful?',
                        style: TextStyle(
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 12),
                      if (_submittedFeedback)
                        const Text(
                          'Thanks for your feedback!',
                          style: TextStyle(
                            color: Color(0xFF059669),
                            fontWeight: FontWeight.bold,
                          ),
                        )
                      else
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Semantics(
                              label: 'Helpful button',
                              child: OutlinedButton.icon(
                                onPressed: () =>
                                    _submitFeedback(article.id, true),
                                icon: const Icon(Icons.thumb_up_alt_outlined,
                                    size: 18),
                                label: const Text('Yes'),
                              ),
                            ),
                            const SizedBox(width: 16),
                            Semantics(
                              label: 'Not helpful button',
                              child: OutlinedButton.icon(
                                onPressed: () =>
                                    _submitFeedback(article.id, false),
                                icon: const Icon(Icons.thumb_down_alt_outlined,
                                    size: 18),
                                label: const Text('No'),
                              ),
                            ),
                          ],
                        ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),

                // Related Articles
                if (article.relatedArticles.isNotEmpty) ...[
                  const Text(
                    'Related Articles',
                    style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 10),
                  ListView.separated(
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    itemCount: article.relatedArticles.length,
                    separatorBuilder: (context, index) =>
                        const SizedBox(height: 8),
                    itemBuilder: (context, index) {
                      final rel = article.relatedArticles[index];
                      return InkWell(
                        onTap: () {
                          Navigator.pushReplacement(
                            context,
                            MaterialPageRoute(
                              builder: (context) =>
                                  KnowledgeArticleScreen(articleSlug: rel.slug),
                            ),
                          );
                        },
                        child: AppCard(
                          padding: const EdgeInsets.all(12),
                          child: Text(
                            rel.title,
                            style: const TextStyle(
                              fontSize: 14,
                              fontWeight: FontWeight.bold,
                              color: AppColors.primary,
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                  const SizedBox(height: 20),
                ],

                // Create Ticket CTA
                AppCard(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    children: [
                      const Text(
                        "This didn't solve your issue?",
                        style: TextStyle(
                            fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      const SizedBox(height: 4),
                      const Text(
                        'Submit a support ticket and our team will get in touch.',
                        textAlign: TextAlign.center,
                        style: TextStyle(color: Colors.grey, fontSize: 12),
                      ),
                      const SizedBox(height: 12),
                      Semantics(
                        label: 'Create Support Ticket',
                        child: ElevatedButton.icon(
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (context) =>
                                    const TicketListScreen(),
                              ),
                            );
                          },
                          icon: const Icon(Icons.confirmation_number_outlined),
                          label: const Text('Create Support Ticket'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
