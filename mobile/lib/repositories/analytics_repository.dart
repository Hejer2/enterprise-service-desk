import 'dart:developer';
import '../models/executive_analytics.dart';
import '../services/api_client.dart';

class AnalyticsRepository {
  final ApiClient _apiClient;

  AnalyticsRepository(this._apiClient);

  Future<ExecutiveAnalytics> getExecutiveAnalytics({String preset = '30_days'}) async {
    try {
      final response = await _apiClient.dio.get('executive-analytics', queryParameters: {'preset': preset});
      if (response.statusCode == 200) {
        return ExecutiveAnalytics.fromJson(response.data);
      }
      throw Exception('Failed to load analytics: ${response.statusCode}');
    } catch (e) {
      log('Failed to fetch executive analytics: $e');
      rethrow;
    }
  }
}
