import 'dart:developer';
import '../models/user.dart';
import '../services/api_client.dart';

class AuthRepository {
  final ApiClient _apiClient;

  AuthRepository(this._apiClient);

  Future<User?> login(String email, String password) async {
    try {
      // Trying the actual backend first
      final response = await _apiClient.dio.post('login', data: {
        'email': email,
        'password': password,
      });

      if (response.statusCode == 200 &&
          response.data != null &&
          response.data['token'] != null) {
        final String token = response.data['token'].toString();
        await _apiClient.setToken(token);

        // Fetch user profile after successful login
        return await getCurrentUser();
      }
      return null;
    } catch (e, stack) {
      log('Login API failed: $e\n$stack');
      rethrow;
    }
  }

  Future<User?> getCurrentUser() async {
    try {
      final response = await _apiClient.dio.get('me');
      if (response.statusCode == 200 && response.data != null) {
        final Map<String, dynamic> map = response.data is Map<String, dynamic>
            ? response.data
            : Map<String, dynamic>.from(response.data as Map);
        return User.fromJson(map);
      }
      return null;
    } catch (e, stack) {
      log('getCurrentUser failed: $e\n$stack');
      return null;
    }
  }

  Future<void> logout() async {
    await _apiClient.clearToken();
  }
}
