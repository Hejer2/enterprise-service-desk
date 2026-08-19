import 'dart:developer';
import '../models/ticket.dart';
import '../services/api_client.dart';

class TicketRepository {
  final ApiClient _apiClient;

  TicketRepository(this._apiClient);

  Future<List<Ticket>> getTickets({
    int page = 1,
    int limit = 25,
    String sort = 'createdAt',
    String order = 'DESC',
  }) async {
    final response = await _apiClient.dio.get('tickets', queryParameters: {
      'page': page,
      'limit': limit,
      'sort': sort,
      'order': order,
    });
    if (response.statusCode == 200) {
      final data = response.data;
      List<dynamic> items = [];
      if (data is List) {
        items = data;
      } else if (data is Map) {
        items = data['items'] ?? data['tickets'] ?? data['data'] ?? [];
      }
      return items.map((json) {
        if (json is Map<String, dynamic>) {
          return Ticket.fromJson(json);
        }
        return Ticket.fromJson(Map<String, dynamic>.from(json as Map));
      }).toList();
    }
    throw Exception('Failed to fetch tickets: HTTP ${response.statusCode}');
  }

  Future<bool> createTicket({
    required String title,
    required String description,
    required String category,
    required String priority,
  }) async {
    try {
      final response = await _apiClient.dio.post('tickets', data: {
        'title': title,
        'description': description,
        'category': category,
        'priority': priority,
      });
      return response.statusCode == 200 || response.statusCode == 201;
    } catch (e) {
      log('Failed to create ticket: $e');
      return false;
    }
  }

  Future<Map<String, dynamic>> getTicketDetails(int id) async {
    try {
      final response = await _apiClient.dio.get('tickets/$id');
      return response.data as Map<String, dynamic>;
    } catch (e) {
      log('Failed to fetch ticket details: $e');
      rethrow;
    }
  }

  Future<List<Map<String, dynamic>>> getTicketMessages(int id) async {
    try {
      final response = await _apiClient.dio.get('tickets/$id/messages');
      if (response.statusCode == 200) {
        return List<Map<String, dynamic>>.from(response.data as List);
      }
      return [];
    } catch (e) {
      log('Failed to fetch ticket messages: $e');
      return [];
    }
  }

  Future<Map<String, dynamic>> getTicketActivities(int id,
      {int page = 1, int limit = 30}) async {
    try {
      final response = await _apiClient.dio.get('tickets/$id/activities',
          queryParameters: {'page': page, 'limit': limit});
      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      return {
        'items': [],
        'page': 1,
        'limit': 30,
        'total': 0,
        'hasMore': false
      };
    } catch (e) {
      log('Failed to fetch ticket activities: $e');
      return {
        'items': [],
        'page': 1,
        'limit': 30,
        'total': 0,
        'hasMore': false
      };
    }
  }

  Future<bool> sendReply(int id, String message,
      {List<Map<String, dynamic>>? attachments}) async {
    try {
      final payload = <String, dynamic>{
        'message': message,
      };
      if (attachments != null && attachments.isNotEmpty) {
        payload['attachments'] = attachments;
      }

      final response = await _apiClient.dio.post(
        'tickets/$id/messages',
        data: payload,
      );
      return response.statusCode == 200 || response.statusCode == 201;
    } catch (e) {
      log('Failed to send reply: $e');
      return false;
    }
  }

  Future<bool> updateStatus(int id, String status) async {
    try {
      final response = await _apiClient.dio.post(
        'tickets/$id/status',
        data: {'status': status},
      );
      return response.statusCode == 200;
    } catch (e) {
      log('Failed to update status: $e');
      return false;
    }
  }

  Future<List<Map<String, dynamic>>> getTechnicians(int ticketId) async {
    try {
      final response =
          await _apiClient.dio.get('tickets/$ticketId/technicians');
      if (response.statusCode == 200) {
        return List<Map<String, dynamic>>.from(response.data as List);
      }
      return [];
    } catch (e) {
      log('Failed to fetch technicians: $e');
      return [];
    }
  }

  Future<bool> assignTechnician(int ticketId, int technicianId) async {
    try {
      final response = await _apiClient.dio.post(
        'tickets/$ticketId/assign',
        data: {'technicianId': technicianId},
      );
      return response.statusCode == 200;
    } catch (e) {
      log('Failed to assign technician: $e');
      return false;
    }
  }

  Future<bool> reopenTicket(int ticketId, String reason) async {
    try {
      final response = await _apiClient.dio.post(
        'tickets/$ticketId/reopen',
        data: {'reason': reason},
      );
      return response.statusCode == 200;
    } catch (e) {
      log('Failed to reopen ticket: $e');
      rethrow;
    }
  }

  Future<bool> submitCsatRating(int ticketId, int rating, String? comment) async {
    try {
      final response = await _apiClient.dio.post(
        'tickets/$ticketId/csat',
        data: {'rating': rating, 'comment': comment},
      );
      return response.statusCode == 200 || response.statusCode == 201;
    } catch (e) {
      log('Failed to submit CSAT rating: $e');
      rethrow;
    }
  }

  Future<List<Map<String, dynamic>>> getCannedResponses() async {
    try {
      final response = await _apiClient.dio.get('canned-responses');
      if (response.statusCode == 200) {
        return List<Map<String, dynamic>>.from(response.data as List);
      }
      return [];
    } catch (e) {
      log('Failed to fetch canned responses: $e');
      return [];
    }
  }

  Future<Map<String, dynamic>> bulkUpdateTickets(List<int> ticketIds, String action, String value) async {
    try {
      final response = await _apiClient.dio.post(
        'tickets/bulk',
        data: {
          'action': action,
          'ticketIds': ticketIds,
          'value': value,
        },
      );
      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      return {'success': false, 'message': 'Bulk update failed'};
    } catch (e) {
      log('Failed bulk update: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> getReports({
    String datePreset = 'last_30_days',
    String? startDate,
    String? endDate,
    String? category,
    int? technicianId,
  }) async {
    try {
      final params = <String, dynamic>{'date_preset': datePreset};
      if (startDate != null) params['start_date'] = startDate;
      if (endDate != null) params['end_date'] = endDate;
      if (category != null) params['category'] = category;
      if (technicianId != null) params['technician'] = technicianId;

      final response = await _apiClient.dio.get('reports', queryParameters: params);
      if (response.statusCode == 200) {
        return response.data as Map<String, dynamic>;
      }
      return {};
    } catch (e) {
      log('Failed to fetch reports: $e');
      rethrow;
    }
  }
}
