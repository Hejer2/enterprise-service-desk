import 'dart:developer';
import '../services/api_client.dart';

class SettingsRepository {
  final ApiClient _apiClient;

  SettingsRepository(this._apiClient);

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    try {
      final response = await _apiClient.dio.post(
        'me/change-password',
        data: {
          'currentPassword': currentPassword,
          'newPassword': newPassword,
          'confirmPassword': confirmPassword,
        },
      );
      if (response.statusCode != 200) {
        throw Exception('Password update failed: ${response.statusCode}');
      }
    } catch (e) {
      log('Change password error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> updateProfile({
    required String firstName,
    required String lastName,
    String? phone,
    String? profilePicture,
  }) async {
    try {
      final response = await _apiClient.dio.post(
        'me/profile',
        data: {
          'firstName': firstName,
          'lastName': lastName,
          if (phone != null) 'phone': phone,
          if (profilePicture != null) 'profilePicture': profilePicture,
        },
      );
      if (response.statusCode == 200 && response.data is Map<String, dynamic>) {
        return response.data as Map<String, dynamic>;
      }
      throw Exception('Profile update failed: ${response.statusCode}');
    } catch (e) {
      log('Update profile error: $e');
      rethrow;
    }
  }

  Future<void> updatePreferences({
    String? language,
    String? theme,
  }) async {
    try {
      final response = await _apiClient.dio.post(
        'me/preferences',
        data: {
          if (language != null) 'language': language,
          if (theme != null) 'theme': theme,
        },
      );
      if (response.statusCode != 200) {
        throw Exception('Preferences update failed: ${response.statusCode}');
      }
    } catch (e) {
      log('Update preferences error: $e');
      rethrow;
    }
  }

  Future<Map<String, dynamic>> getNotificationPreferences() async {
    try {
      final response = await _apiClient.dio.get('notifications/preferences');
      if (response.statusCode == 200 && response.data is Map<String, dynamic>) {
        return response.data as Map<String, dynamic>;
      }
      throw Exception('Failed to load notification preferences: ${response.statusCode}');
    } catch (e) {
      log('Get notification preferences error: $e');
      rethrow;
    }
  }

  Future<void> saveNotificationPreferences(Map<String, dynamic> preferences) async {
    try {
      final response = await _apiClient.dio.post(
        'notifications/preferences',
        data: preferences,
      );
      if (response.statusCode != 200) {
        throw Exception('Failed to save notification preferences: ${response.statusCode}');
      }
    } catch (e) {
      log('Save notification preferences error: $e');
      rethrow;
    }
  }
}
