import 'dart:developer';
import 'package:dio/dio.dart';
import '../services/api_client.dart';

class UserRepository {
  final ApiClient _apiClient;

  UserRepository(this._apiClient);

  Future<List<Map<String, dynamic>>> getUsers() async {
    try {
      final response = await _apiClient.dio.get('users');
      if (response.statusCode == 200) {
        final List<dynamic> data = response.data;
        return data.cast<Map<String, dynamic>>();
      }
      return [];
    } on DioException catch (e) {
      log('Failed to fetch users: ${e.message}');
      throw Exception('Failed to load users.');
    }
  }

  Future<bool> createUser(Map<String, dynamic> userData) async {
    try {
      final response = await _apiClient.dio.post('users', data: userData);
      return response.statusCode == 200 || response.statusCode == 201;
    } catch (e) {
      log('Failed to create user: $e');
      return false;
    }
  }

  Future<bool> updateUser(int id, Map<String, dynamic> userData) async {
    try {
      final response = await _apiClient.dio.post('users/$id', data: userData);
      return response.statusCode == 200;
    } catch (e) {
      log('Failed to update user: $e');
      return false;
    }
  }

  Future<bool> deleteUser(int id) async {
    try {
      final response = await _apiClient.dio.delete('users/$id');
      return response.statusCode == 200;
    } catch (e) {
      try {
        final res =
            await _apiClient.dio.post('users/$id', data: {'_method': 'DELETE'});
        return res.statusCode == 200;
      } catch (_) {
        log('Failed to delete user: $e');
        return false;
      }
    }
  }
}
