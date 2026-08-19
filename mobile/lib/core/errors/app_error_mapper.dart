import 'package:dio/dio.dart';

class AppErrorPresentation {
  final String title;
  final String message;
  final String category; // 'network', 'server', 'permission', 'auth', 'unknown'
  final bool canRetry;

  const AppErrorPresentation({
    required this.title,
    required this.message,
    required this.category,
    this.canRetry = true,
  });
}

class AppErrorMapper {
  static AppErrorPresentation map(dynamic error) {
    if (error is DioException) {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
        case DioExceptionType.connectionError:
          return const AppErrorPresentation(
            title: 'Connection Error',
            message: 'Unable to reach the server. Please check your network connection.',
            category: 'network',
            canRetry: true,
          );
        case DioExceptionType.badResponse:
          final statusCode = error.response?.statusCode ?? 500;
          if (statusCode == 401) {
            return const AppErrorPresentation(
              title: 'Session Expired',
              message: 'Your session has expired. Please log in again.',
              category: 'auth',
              canRetry: false,
            );
          } else if (statusCode == 403) {
            return const AppErrorPresentation(
              title: 'Access Denied',
              message: 'You do not have permission to view this resource.',
              category: 'permission',
              canRetry: false,
            );
          } else if (statusCode >= 500) {
            return const AppErrorPresentation(
              title: 'Server Error',
              message: 'The server encountered an error. Please try again later.',
              category: 'server',
              canRetry: true,
            );
          }
          break;
        default:
          break;
      }
    }

    return const AppErrorPresentation(
      title: 'Something Went Wrong',
      message: 'We could not complete your request. Please try again.',
      category: 'unknown',
      canRetry: true,
    );
  }
}
