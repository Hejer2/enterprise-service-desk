class KnowledgeArticle {
  final int id;
  final String title;
  final String slug;
  final String? excerpt;
  final String? content;
  final String categoryName;
  final String categorySlug;
  final int viewCount;
  final int helpfulCount;
  final int notHelpfulCount;
  final double helpfulPercentage;
  final String? authorName;
  final DateTime? publishedAt;
  final List<KnowledgeArticle> relatedArticles;

  KnowledgeArticle({
    required this.id,
    required this.title,
    required this.slug,
    this.excerpt,
    this.content,
    required this.categoryName,
    required this.categorySlug,
    this.viewCount = 0,
    this.helpfulCount = 0,
    this.notHelpfulCount = 0,
    this.helpfulPercentage = 100.0,
    this.authorName,
    this.publishedAt,
    this.relatedArticles = const [],
  });

  factory KnowledgeArticle.fromJson(Map<String, dynamic> json) {
    List<KnowledgeArticle> rels = [];
    if (json['relatedArticles'] != null && json['relatedArticles'] is List) {
      rels = (json['relatedArticles'] as List)
          .map((item) => KnowledgeArticle.fromJson(item as Map<String, dynamic>))
          .toList();
    }

    return KnowledgeArticle(
      id: (json['id'] as num?)?.toInt() ?? 0,
      title: json['title']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      excerpt: json['excerpt']?.toString(),
      content: json['content']?.toString(),
      categoryName: json['categoryName']?.toString() ?? '',
      categorySlug: json['categorySlug']?.toString() ?? '',
      viewCount: (json['viewCount'] as num?)?.toInt() ?? 0,
      helpfulCount: (json['helpfulCount'] as num?)?.toInt() ?? 0,
      notHelpfulCount: (json['notHelpfulCount'] as num?)?.toInt() ?? 0,
      helpfulPercentage: (json['helpfulPercentage'] as num?)?.toDouble() ?? 100.0,
      authorName: json['authorName']?.toString(),
      publishedAt: json['publishedAt'] != null
          ? DateTime.tryParse(json['publishedAt'].toString())
          : null,
      relatedArticles: rels,
    );
  }
}
