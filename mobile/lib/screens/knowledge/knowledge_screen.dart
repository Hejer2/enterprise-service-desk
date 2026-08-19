import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_state.dart';
import '../../core/widgets/skeleton_loader.dart';
import '../../models/knowledge_article.dart';
import '../../models/knowledge_category.dart';
import '../../repositories/knowledge_repository.dart';
import '../../services/api_client.dart';
import '../tickets/ticket_list_screen.dart';
import 'knowledge_article_screen.dart';

final knowledgeRepositoryProvider =
    Provider((ref) => KnowledgeRepository(ref.read(apiClientProvider)));

final knowledgeHomeDataProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final repo = ref.read(knowledgeRepositoryProvider);
  return repo.getKnowledgeHomeData();
});

class KnowledgeScreen extends ConsumerStatefulWidget {
  const KnowledgeScreen({super.key});

  @override
  ConsumerState<KnowledgeScreen> createState() => _KnowledgeScreenState();
}

class _KnowledgeScreenState extends ConsumerState<KnowledgeScreen> {
  final TextEditingController _searchController = TextEditingController();
  Timer? _debounceTimer;
  bool _isSearching = false;
  bool _isLoadingSearch = false;
  List<KnowledgeArticle> _searchResults = [];

  @override
  void dispose() {
    _searchController.dispose();
    _debounceTimer?.cancel();
    super.dispose();
  }

  void _onSearchChanged(String query) {
    _debounceTimer?.cancel();
    final q = query.trim();

    if (q.isEmpty) {
      setState(() {
        _isSearching = false;
        _searchResults = [];
      });
      return;
    }

    setState(() {
      _isSearching = true;
      _isLoadingSearch = true;
    });

    _debounceTimer = Timer(const Duration(milliseconds: 300), () async {
      try {
        final repo = ref.read(knowledgeRepositoryProvider);
        final results = await repo.searchArticles(query: q);
        if (mounted) {
          setState(() {
            _searchResults = results;
            _isLoadingSearch = false;
          });
        }
      } catch (e) {
        if (mounted) {
          setState(() {
            _isLoadingSearch = false;
          });
        }
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDesktop = MediaQuery.of(context).size.width >= 768;
    final homeDataAsync = ref.watch(knowledgeHomeDataProvider);

    return Scaffold(
      body: SingleChildScrollView(
        padding: EdgeInsets.all(isDesktop ? 24 : 14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header & Hero Search
            Text(
              'Knowledge Base',
              style: TextStyle(
                fontSize: isDesktop ? 28 : 22,
                fontWeight: FontWeight.w800,
                color: theme.colorScheme.onSurface,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              'Search for solutions or browse common troubleshooting articles.',
              style: TextStyle(
                color: theme.colorScheme.onSurface.withOpacity(0.6),
                fontSize: isDesktop ? 14 : 13,
              ),
            ),
            const SizedBox(height: 16),

            // Search Bar
            Semantics(
              label: 'Search Knowledge Base',
              child: TextField(
                controller: _searchController,
                onChanged: _onSearchChanged,
                decoration: InputDecoration(
                  hintText: 'Search for passwords, Wi-Fi, machinery...',
                  prefixIcon: const Icon(Icons.search),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear),
                          onPressed: () {
                            _searchController.clear();
                            _onSearchChanged('');
                          },
                        )
                      : null,
                  filled: true,
                  fillColor: theme.colorScheme.surface,
                  border: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(12),
                    borderSide: BorderSide(
                        color: theme.colorScheme.onSurface.withOpacity(0.2)),
                  ),
                ),
              ),
            ),
            const SizedBox(height: 24),

            // Content Area: Search Results OR Home Categories/Articles
            if (_isSearching) ...[
              if (_isLoadingSearch)
                const SkeletonLoader(width: double.infinity, height: 100)
              else if (_searchResults.isEmpty)
                EmptyState(
                  icon: Icons.search_off_rounded,
                  title: 'No articles found',
                  message:
                      'No matching Knowledge Base articles found. Create a support ticket to get help.',
                  actionText: 'Go to Tickets',
                  onActionPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (context) => const TicketListScreen(),
                      ),
                    );
                  },
                )
              else
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _searchResults.length,
                  separatorBuilder: (context, index) =>
                      const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final article = _searchResults[index];
                    return _buildArticleListItem(context, article);
                  },
                ),
            ] else ...[
              homeDataAsync.when(
                loading: () => const SkeletonLoader(width: double.infinity, height: 120),
                error: (err, stack) => ErrorState(
                  title: 'Failed to load Knowledge Base',
                  message: err.toString(),
                  onRetry: () => ref.invalidate(knowledgeHomeDataProvider),
                ),
                data: (data) {
                  final categoriesJson =
                      data['categories'] as List<dynamic>? ?? [];
                  final popularJson =
                      data['popularArticles'] as List<dynamic>? ?? [];
                  final recentJson =
                      data['recentArticles'] as List<dynamic>? ?? [];

                  final categories = categoriesJson
                      .map((json) => KnowledgeCategory.fromJson(json))
                      .toList();
                  final popularArticles = popularJson
                      .map((json) => KnowledgeArticle.fromJson(json))
                      .toList();
                  final recentArticles = recentJson
                      .map((json) => KnowledgeArticle.fromJson(json))
                      .toList();

                  return Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Categories Section
                      if (categories.isNotEmpty) ...[
                        const Text(
                          'Categories',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        Wrap(
                          spacing: 10,
                          runSpacing: 10,
                          children: categories.map((cat) {
                            return InkWell(
                              onTap: () {
                                _searchController.text = cat.name;
                                _onSearchChanged(cat.name);
                              },
                              borderRadius: BorderRadius.circular(12),
                              child: AppCard(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 14, vertical: 10),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Text(cat.icon,
                                        style: const TextStyle(fontSize: 18)),
                                    const SizedBox(width: 8),
                                    Text(
                                      cat.name,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          fontSize: 13),
                                    ),
                                  ],
                                ),
                              ),
                            );
                          }).toList(),
                        ),
                        const SizedBox(height: 24),
                      ],

                      // Popular Articles
                      if (popularArticles.isNotEmpty) ...[
                        const Text(
                          'Popular Articles',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: popularArticles.length,
                          separatorBuilder: (context, index) =>
                              const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            return _buildArticleListItem(
                                context, popularArticles[index]);
                          },
                        ),
                        const SizedBox(height: 24),
                      ],

                      // Recently Updated
                      if (recentArticles.isNotEmpty) ...[
                        const Text(
                          'Recently Updated',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 12),
                        ListView.separated(
                          shrinkWrap: true,
                          physics: const NeverScrollableScrollPhysics(),
                          itemCount: recentArticles.length,
                          separatorBuilder: (context, index) =>
                              const SizedBox(height: 8),
                          itemBuilder: (context, index) {
                            return _buildArticleListItem(
                                context, recentArticles[index]);
                          },
                        ),
                        const SizedBox(height: 24),
                      ],

                      // Create Ticket CTA Card
                      AppCard(
                        padding: const EdgeInsets.all(16),
                        child: Row(
                          children: [
                            const Icon(Icons.help_outline,
                                color: AppColors.primary, size: 28),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Text(
                                    'Still need assistance?',
                                    style: TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 14),
                                  ),
                                  Text(
                                    'Our service desk technicians are available to help.',
                                    style: TextStyle(
                                        color: Colors.grey.shade600,
                                        fontSize: 12),
                                  ),
                                ],
                              ),
                            ),
                            ElevatedButton(
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (context) =>
                                        const TicketListScreen(),
                                  ),
                                );
                              },
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.primary,
                                foregroundColor: Colors.white,
                              ),
                              child: const Text('Create Ticket'),
                            ),
                          ],
                        ),
                      ),
                    ],
                  );
                },
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildArticleListItem(BuildContext context, KnowledgeArticle article) {
    return InkWell(
      onTap: () {
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) =>
                KnowledgeArticleScreen(articleSlug: article.slug),
          ),
        );
      },
      borderRadius: BorderRadius.circular(12),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  article.title,
                  style: const TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                    color: AppColors.primary,
                  ),
                ),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    article.categoryName,
                    style: const TextStyle(
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                      color: AppColors.primary,
                    ),
                  ),
                ),
              ],
            ),
            if (article.excerpt != null && article.excerpt!.isNotEmpty) ...[
              const SizedBox(height: 6),
              Text(
                article.excerpt!,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 12, color: Colors.grey),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
