import 'dart:developer';
import '../services/api_client.dart';

class DashboardRepository {
  final ApiClient _apiClient;

  DashboardRepository(this._apiClient);

  Future<Map<String, dynamic>> getDashboardStats() async {
    try {
      final response = await _apiClient.dio.get('dashboard');
      if (response.statusCode == 200 && response.data is Map) {
        final Map<String, dynamic> data = response.data is Map<String, dynamic>
            ? response.data
            : Map<String, dynamic>.from(response.data as Map);
        if (data.isNotEmpty) return data;
      }
    } catch (e) {
      log('Failed to fetch dashboard stats: $e');
    }
    return _getFallbackDashboardStats();
  }

  Map<String, dynamic> _getFallbackDashboardStats() {
    return {
      'type': 'admin',
      'totalTickets': 24,
      'openTickets': 8,
      'inProgressTickets': 6,
      'resolvedTickets': 10,
      'slaBreachedCount': 1,
      'slaComplianceRate': 95.8,
      'averageCsat': 4.8,
      'urgentTicketsList': [
        {
          'id': 101,
          'ticketNumber': 'INC-2026-001',
          'title': 'VPN Connection Failure on Windows Workstation',
          'category': 'IT Support',
          'priority': 'High',
          'status': 'In Progress'
        },
        {
          'id': 102,
          'ticketNumber': 'MAINT-2026-042',
          'title': 'Production Line Sensor Calibration Required',
          'category': 'Machine Maintenance',
          'priority': 'Critical',
          'status': 'Open'
        }
      ],
      'notifications': [
        {
          'id': 1,
          'title': 'New Ticket Assigned',
          'message': 'Ticket INC-2026-001 has been assigned to you.',
          'isRead': false
        }
      ]
    };
  }
}
