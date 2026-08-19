import 'dart:developer';
import 'package:dio/dio.dart';
import '../models/knowledge_article.dart';
import '../models/knowledge_category.dart';
import '../services/api_client.dart';

class KnowledgeRepository {
  final ApiClient _apiClient;

  KnowledgeRepository(this._apiClient);

  Future<List<KnowledgeCategory>> getCategories() async {
    try {
      final response = await _apiClient.dio.get('knowledge/categories');
      if (response.statusCode == 200) {
        final List<dynamic> data = response.data;
        return data.map((json) => KnowledgeCategory.fromJson(json)).toList();
      }
      return [];
    } on DioException catch (e) {
      log('Failed to fetch KB categories: ${e.message}');
      throw Exception('Failed to load categories.');
    }
  }

  Future<Map<String, dynamic>> getKnowledgeHomeData() async {
    try {
      final response = await _apiClient.dio.get('knowledge');
      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      return {'categories': [], 'popularArticles': [], 'recentArticles': []};
    } catch (e) {
      log('Failed to fetch KB home data: $e');
      rethrow;
    }
  }

  Future<List<KnowledgeArticle>> searchArticles({
    String query = '',
    int? categoryId,
    int page = 1,
    int limit = 15,
  }) async {
    try {
      final params = <String, dynamic>{
        'q': query,
        'page': page,
        'limit': limit,
      };
      if (categoryId != null) params['category'] = categoryId;

      final response =
          await _apiClient.dio.get('knowledge/search', queryParameters: params);
      if (response.statusCode == 200) {
        final List<dynamic> items = response.data['items'] ?? [];
        return items.map((json) => KnowledgeArticle.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      log('Failed KB search: $e');
      return [];
    }
  }

  Future<KnowledgeArticle> getArticleDetails(String slug) async {
    try {
      final response = await _apiClient.dio.get('knowledge/articles/$slug');
      if (response.statusCode == 200) {
        return KnowledgeArticle.fromJson(response.data as Map<String, dynamic>);
      }
      throw Exception('Article not found');
    } catch (e) {
      log('Failed to fetch KB article details: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> submitFeedback(int articleId, bool helpful) async {
    try {
      final response = await _apiClient.dio.post(
        'knowledge/articles/$articleId/feedback',
        data: {'helpful': helpful},
      );
      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      return {'success': false};
    } catch (e) {
      log('Failed to submit KB feedback: $e');
      rethrow;
    }
  }
}
