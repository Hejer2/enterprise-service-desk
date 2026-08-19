import 'package:flutter/material.dart';

enum AppToastType { success, error, warning, info }

class AppToast {
  static void show(
    BuildContext context, {
    required String message,
    AppToastType type = AppToastType.info,
    Duration duration = const Duration(seconds: 3, milliseconds: 500),
  }) {
    final scaffoldMessenger = ScaffoldMessenger.maybeOf(context);
    if (scaffoldMessenger == null) return;

    Color bg;
    IconData icon;

    switch (type) {
      case AppToastType.success:
        bg = const Color(0xFF10B981);
        icon = Icons.check_circle_outline_rounded;
        break;
      case AppToastType.error:
        bg = const Color(0xFFEF4444);
        icon = Icons.error_outline_rounded;
        break;
      case AppToastType.warning:
        bg = const Color(0xFFF59E0B);
        icon = Icons.warning_amber_rounded;
        break;
      case AppToastType.info:
        bg = const Color(0xFF2563EB);
        icon = Icons.info_outline_rounded;
        break;
    }

    scaffoldMessenger.hideCurrentSnackBar();
    scaffoldMessenger.showSnackBar(
      SnackBar(
        duration: duration,
        behavior: SnackBarBehavior.floating,
        backgroundColor: Colors.transparent,
        elevation: 0,
        margin: const EdgeInsets.all(16),
        padding: EdgeInsets.zero,
        content: Container(
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          decoration: BoxDecoration(
            color: const Color(0xFF0F172A),
            borderRadius: BorderRadius.circular(10),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.15),
                blurRadius: 10,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            children: [
              Icon(icon, color: bg, size: 20),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  message,
                  style: const TextStyle(
                    color: Colors.white,
                    fontSize: 13.5,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              InkWell(
                onTap: () => scaffoldMessenger.hideCurrentSnackBar(),
                borderRadius: BorderRadius.circular(12),
                child: const Padding(
                  padding: EdgeInsets.all(4.0),
                  child: Icon(Icons.close_rounded, color: Color(0xFF94A3B8), size: 16),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  static void showSuccess(BuildContext context, String message) =>
      show(context, message: message, type: AppToastType.success);

  static void showError(BuildContext context, String message) =>
      show(context, message: message, type: AppToastType.error);

  static void showWarning(BuildContext context, String message) =>
      show(context, message: message, type: AppToastType.warning);

  static void showInfo(BuildContext context, String message) =>
      show(context, message: message, type: AppToastType.info);
}
