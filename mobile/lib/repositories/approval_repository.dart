import 'dart:developer';
import '../models/approval_request.dart';
import '../services/api_client.dart';

class ApprovalRepository {
  final ApiClient _apiClient;

  ApprovalRepository(this._apiClient);

  Future<List<ApprovalRequest>> getPendingApprovals() async {
    try {
      final response = await _apiClient.dio.get('approvals/pending');
      if (response.statusCode == 200) {
        final List<dynamic> data = response.data;
        return data.map((json) => ApprovalRequest.fromJson(json)).toList();
      }
      return [];
    } catch (e) {
      log('Failed to fetch pending approvals: $e');
      rethrow;
    }
  }

  Future<void> respondApproval(int id, String action, {String? comment}) async {
    try {
      await _apiClient.dio.post(
        'approvals/$id/respond',
        data: {'action': action, 'comment': comment},
      );
    } catch (e) {
      log('Failed to respond to approval: $e');
      rethrow;
    }
  }
}
