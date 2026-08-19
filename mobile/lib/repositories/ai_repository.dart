import 'dart:developer';
import '../models/ai_reply.dart';
import '../models/ai_summary.dart';
import '../models/ai_ticket_analysis.dart';
import '../services/api_client.dart';

class AiRepository {
  final ApiClient _apiClient;

  AiRepository(this._apiClient);

  Future<AiTicketAnalysis> classifyTicket(int ticketId) async {
    try {
      final response = await _apiClient.dio.post('ai/tickets/$ticketId/classify');
      if (response.statusCode == 200) {
        return AiTicketAnalysis.fromJson(response.data);
      }
      throw Exception('AI classification failed: ${response.statusCode}');
    } catch (e) {
      log('AI Classification error: $e');
      rethrow;
    }
  }

  Future<AiSummary> summarizeTicket(int ticketId) async {
    try {
      final response = await _apiClient.dio.post('ai/tickets/$ticketId/summarize');
      if (response.statusCode == 200) {
        return AiSummary.fromJson(response.data);
      }
      throw Exception('AI summarization failed: ${response.statusCode}');
    } catch (e) {
      log('AI Summarization error: $e');
      rethrow;
    }
  }

  Future<AiReply> generateReply(int ticketId, {String action = 'generate', String? context}) async {
    try {
      final response = await _apiClient.dio.post(
        'ai/tickets/$ticketId/reply',
        data: {
          'action': action,
          if (context != null) 'context': context,
        },
      );
      if (response.statusCode == 200) {
        return AiReply.fromJson(response.data);
      }
      throw Exception('AI reply generation failed: ${response.statusCode}');
    } catch (e) {
      log('AI Reply generation error: $e');
      rethrow;
    }
  }

  Future<List<Map<String, dynamic>>> findSimilarTickets(int ticketId) async {
    try {
      final response = await _apiClient.dio.post('ai/tickets/$ticketId/similar');
      if (response.statusCode == 200) {
        final data = response.data;
        if (data is Map && data['similarTickets'] is List) {
          return List<Map<String, dynamic>>.from(data['similarTickets']);
        }
        if (data is List) {
          return List<Map<String, dynamic>>.from(data);
        }
      }
      return [];
    } catch (e) {
      log('AI Similar Tickets error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> recommendResolution(int ticketId) async {
    try {
      final response = await _apiClient.dio.post('ai/tickets/$ticketId/resolution');
      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      throw Exception('AI Resolution recommendation failed: ${response.statusCode}');
    } catch (e) {
      log('AI Resolution error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> askKnowledge(int ticketId, {String? query}) async {
    try {
      final response = await _apiClient.dio.post(
        'ai/tickets/$ticketId/knowledge',
        data: {
          if (query != null && query.isNotEmpty) 'query': query,
        },
      );
      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      throw Exception('AI Knowledge Base query failed: ${response.statusCode}');
    } catch (e) {
      log('AI Knowledge error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> generateExecutiveInsights() async {
    try {
      final response = await _apiClient.dio.post('ai/executive-insights');
      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      throw Exception('AI Executive Insights failed: ${response.statusCode}');
    } catch (e) {
      log('AI Executive Insights error: $e');
      rethrow;
    }
  }
}
