class KnowledgeCategory {
  final int id;
  final String name;
  final String slug;
  final String? description;
  final String icon;

  KnowledgeCategory({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    this.icon = '📁',
  });

  factory KnowledgeCategory.fromJson(Map<String, dynamic> json) {
    return KnowledgeCategory(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      description: json['description']?.toString(),
      icon: json['icon']?.toString() ?? '📁',
    );
  }
}
