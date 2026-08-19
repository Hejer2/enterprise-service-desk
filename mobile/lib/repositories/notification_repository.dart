import 'dart:developer';
import '../models/user_notification.dart';
import '../services/api_client.dart';

class NotificationRepository {
  final ApiClient _apiClient;

  NotificationRepository(this._apiClient);

  Future<List<UserNotification>> getNotifications({int page = 1, int limit = 50}) async {
    final response = await _apiClient.dio.get('notifications', queryParameters: {
      'page': page,
      'limit': limit,
    });
    if (response.statusCode == 200) {
      final data = response.data;
      List<dynamic> items = [];
      if (data is List) {
        items = data;
      } else if (data is Map) {
        items = data['items'] ?? data['notifications'] ?? data['data'] ?? [];
      }
      return items.map((json) {
        if (json is Map<String, dynamic>) {
          return UserNotification.fromJson(json);
        }
        return UserNotification.fromJson(Map<String, dynamic>.from(json as Map));
      }).toList();
    }
    throw Exception('Failed to fetch notifications: HTTP ${response.statusCode}');
  }

  Future<int> getUnreadCount() async {
    try {
      final response = await _apiClient.dio.get('notifications/unread-count');
      if (response.statusCode == 200) {
        return (response.data['count'] as num?)?.toInt() ?? 0;
      }
      return 0;
    } catch (e) {
      log('Failed to fetch unread count: $e');
      return 0;
    }
  }

  Future<void> markAsRead(int id) async {
    try {
      await _apiClient.dio.post('notifications/$id/read');
    } catch (e) {
      log('Failed to mark notification read: $e');
      rethrow;
    }
  }

  Future<void> markAllAsRead() async {
    try {
      await _apiClient.dio.post('notifications/read-all');
    } catch (e) {
      log('Failed to mark all read: $e');
      rethrow;
    }
  }

  Future<void> deleteNotification(int id) async {
    try {
      await _apiClient.dio.delete('notifications/$id');
    } catch (e) {
      log('Failed to delete notification: $e');
      rethrow;
    }
  }
}
