import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/models/knowledge_article.dart';
import 'package:mobile/models/knowledge_category.dart';

void main() {
  group('Knowledge Base Defensive Model Tests', () {
    test('KnowledgeCategory.fromJson defensive parsing', () {
      final json = {
        'id': 1,
        'name': 'Account & Access',
        'slug': 'account-access',
        'icon': '🔑',
      };
      final cat = KnowledgeCategory.fromJson(json);
      expect(cat.id, equals(1));
      expect(cat.name, equals('Account & Access'));
      expect(cat.icon, equals('🔑'));
    });

    test('KnowledgeArticle.fromJson defensive parsing with related articles', () {
      final json = {
        'id': 10,
        'title': 'How to reset your domain password',
        'slug': 'how-to-reset-your-domain-password',
        'categoryName': 'Account & Access',
        'categorySlug': 'account-access',
        'viewCount': 42,
        'helpfulPercentage': 95.5,
        'relatedArticles': [
          {
            'id': 11,
            'title': 'Connecting to Wi-Fi',
            'slug': 'connecting-to-wifi',
            'categoryName': 'IT Support',
            'categorySlug': 'it-support',
          }
        ]
      };

      final article = KnowledgeArticle.fromJson(json);
      expect(article.id, equals(10));
      expect(article.title, equals('How to reset your domain password'));
      expect(article.helpfulPercentage, equals(95.5));
      expect(article.relatedArticles.length, equals(1));
      expect(article.relatedArticles.first.title, equals('Connecting to Wi-Fi'));
    });
  });
}
