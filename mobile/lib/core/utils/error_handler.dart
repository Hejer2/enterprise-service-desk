import 'dart:io';
import 'package:dio/dio.dart';

class AppErrorHandler {
  static String getReadableErrorMessage(dynamic error, {String defaultMessage = 'An unexpected error occurred. Please try again.'}) {
    if (error == null) return defaultMessage;

    if (error is String) {
      if (error.contains('DioException') || error.contains('SocketException') || error.contains('HttpException')) {
        return 'Unable to connect to the server. Please check your internet connection.';
      }
      return error;
    }

    if (error is SocketException) {
      return 'Unable to connect to the server. Please check your internet connection.';
    }

    if (error is DioException) {
      switch (error.type) {
        case DioExceptionType.connectionTimeout:
        case DioExceptionType.sendTimeout:
        case DioExceptionType.receiveTimeout:
          return 'The request took too long to respond. Please try again.';

        case DioExceptionType.connectionError:
          return 'Unable to connect to the server. Please try again.';

        case DioExceptionType.badResponse:
          final statusCode = error.response?.statusCode;
          final responseData = error.response?.data;

          if (responseData is Map && responseData.containsKey('error')) {
            final err = responseData['error'];
            if (err is String && err.isNotEmpty && !err.toLowerCase().contains('exception')) {
              if (statusCode == 401 && (err.toLowerCase().contains('invalid') || err.toLowerCase().contains('unauthorized'))) {
                return 'Incorrect email or password.';
              }
              if (err.toLowerCase().contains('current password is invalid')) {
                return 'Current password is incorrect.';
              }
              return err;
            }
          }

          if (statusCode == 401) {
            return 'Incorrect email or password.';
          } else if (statusCode == 403) {
            return "You don't have permission to perform this action.";
          } else if (statusCode == 404) {
            return 'The requested information could not be found.';
          } else if (statusCode == 422 || statusCode == 400) {
            return 'Please check the information you entered.';
          } else if (statusCode != null && statusCode >= 500) {
            return 'Something went wrong on the server. Please try again later.';
          }
          return defaultMessage;

        case DioExceptionType.cancel:
          return 'The request was cancelled.';

        default:
          return 'Unable to connect to the server. Please try again.';
      }
    }

    return defaultMessage;
  }
}
