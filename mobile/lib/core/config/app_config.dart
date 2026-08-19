import 'package:flutter/foundation.dart';

class AppConfig {
  /// Environment-configurable API Base URL.
  /// Can be overridden via command-line: --dart-define=API_URL=http://192.168.1.50:8000/api
  static const String _envApiUrl = String.fromEnvironment(
    'API_URL',
    defaultValue: '',
  );

  static String get baseUrl {
    if (_envApiUrl.isNotEmpty) {
      return normalizeUrl(_envApiUrl);
    }

    if (kIsWeb) {
      final host = Uri.base.host.isNotEmpty ? Uri.base.host : '127.0.0.1';
      // Default to Symfony API backend port (8000) rather than Flutter web dev server port
      return normalizeUrl('http://$host:8000/api');
    }

    if (defaultTargetPlatform == TargetPlatform.android) {
      // Default Android emulator host loopback address
      return normalizeUrl('http://10.0.2.2:8000/api');
    }

    // Default iOS simulator / desktop address
    return normalizeUrl('http://127.0.0.1:8000/api');
  }

  /// Normalizes base URL so it cleanly ends with '/api/' without duplicate '/api/api/' segments.
  static String normalizeUrl(String url) {
    String trimmed = url.trim();
    while (trimmed.endsWith('/')) {
      trimmed = trimmed.substring(0, trimmed.length - 1);
    }
    if (!trimmed.endsWith('/api') && !trimmed.contains('/api/')) {
      trimmed = '$trimmed/api';
    }
    return '$trimmed/';
  }
}
