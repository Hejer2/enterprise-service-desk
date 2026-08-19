import 'package:dio/dio.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/config/app_config.dart';

final apiClientProvider = Provider<ApiClient>((ref) => ApiClient());

/// Callback typedef for handling session expiration (401 response).
typedef SessionExpiredCallback = void Function();

class ApiClient {
  static String get baseUrl => AppConfig.baseUrl;

  final Dio dio;
  final FlutterSecureStorage _storage;
  String? _memoryToken;
  SessionExpiredCallback? onSessionExpired;
  bool _isHandlingSessionExpired = false;

  ApiClient({String? customBaseUrl, this.onSessionExpired})
      : dio = Dio(BaseOptions(
          baseUrl: customBaseUrl ?? AppConfig.baseUrl,
          connectTimeout: const Duration(seconds: 10),
          receiveTimeout: const Duration(seconds: 10),
        )),
        _storage = const FlutterSecureStorage() {
    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await _getToken();
        if (token != null && token.isNotEmpty) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        options.headers['Accept'] = 'application/json';
        return handler.next(options);
      },
      onError: (DioException e, handler) async {
        if (e.response?.statusCode == 401 && !_isHandlingSessionExpired) {
          _isHandlingSessionExpired = true;
          await clearToken();
          onSessionExpired?.call();
          Future.delayed(const Duration(seconds: 2), () {
            _isHandlingSessionExpired = false;
          });
        }
        return handler.next(e);
      },
    ));
  }

  Future<String?> _getToken() async {
    if (_memoryToken != null && _memoryToken!.isNotEmpty) {
      return _memoryToken;
    }
    try {
      _memoryToken = await _storage.read(key: 'jwt_token');
      return _memoryToken;
    } catch (_) {
      return _memoryToken;
    }
  }

  Future<void> setToken(String token) async {
    _memoryToken = token;
    dio.options.headers['Authorization'] = 'Bearer $token';
    try {
      await _storage.write(key: 'jwt_token', value: token);
    } catch (_) {
      // Gracefully handle HTTP Web origins where Web Crypto API is unavailable
    }
  }

  Future<void> clearToken() async {
    _memoryToken = null;
    dio.options.headers.remove('Authorization');
    try {
      await _storage.delete(key: 'jwt_token');
    } catch (_) {
      // Gracefully handle HTTP Web origins where Web Crypto API is unavailable
    }
  }
}
