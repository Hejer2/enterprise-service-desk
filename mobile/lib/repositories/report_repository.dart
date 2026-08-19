import 'dart:developer';
import 'package:dio/dio.dart';
import '../services/api_client.dart';

class ReportRepository {
  final ApiClient _apiClient;

  ReportRepository(this._apiClient);

  Future<Map<String, dynamic>> getReportData({
    String datePreset = 'last_30_days',
    String? startDate,
    String? endDate,
    String? category,
    String? technicianId,
  }) async {
    try {
      final response = await _apiClient.dio.get(
        'reports',
        queryParameters: {
          'date_preset': datePreset,
          if (startDate != null && startDate.isNotEmpty) 'start_date': startDate,
          if (endDate != null && endDate.isNotEmpty) 'end_date': endDate,
          if (category != null && category.isNotEmpty) 'category': category,
          if (technicianId != null && technicianId.isNotEmpty) 'technician': technicianId,
        },
      );

      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      return {
        'totalTickets': 0,
        'resolvedTickets': 0,
        'avgResolutionTime': 0.0,
        'csatAverage': 0.0,
        'csatCount': 0,
        'csatDistribution': {},
        'technicians': [],
        'tickets': [],
      };
    } on DioException catch (e) {
      log('Failed to fetch report data: ${e.message}');
      throw Exception('Failed to load report data.');
    }
  }
}
