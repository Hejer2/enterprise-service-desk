import 'package:flutter/material.dart';
import '../errors/app_error_mapper.dart';
import '../theme/app_theme.dart';
import 'app_button.dart';

class ErrorState extends StatelessWidget {
  final String? title;
  final String? message;
  final String? description;
  final dynamic error;
  final VoidCallback? onRetry;

  const ErrorState({
    super.key,
    this.title,
    this.message,
    this.description,
    this.error,
    this.onRetry,
  });

  @override
  Widget build(BuildContext context) {
    final effectiveMessage = message ?? description;
    final presentation = error != null
        ? AppErrorMapper.map(error)
        : AppErrorPresentation(
            title: title ?? 'Something Went Wrong',
            message: effectiveMessage ??
                'Unable to load data. Please check your connection and try again.',
            category: 'unknown',
            canRetry: onRetry != null,
          );

    final displayTitle = title ?? presentation.title;
    final displayMessage = effectiveMessage ?? presentation.message;

    IconData errorIcon = Icons.error_outline_rounded;
    Color iconColor = AppColors.danger;
    Color bgColor = AppColors.dangerLight;

    if (presentation.category == 'network') {
      errorIcon = Icons.wifi_off_rounded;
      iconColor = AppColors.warning;
      bgColor = AppColors.warningLight;
    } else if (presentation.category == 'permission' ||
        presentation.category == 'auth') {
      errorIcon = Icons.lock_outline_rounded;
      iconColor = AppColors.info;
      bgColor = AppColors.infoLight;
    } else if (presentation.category == 'server') {
      errorIcon = Icons.dns_outlined;
      iconColor = AppColors.danger;
      bgColor = AppColors.dangerLight;
    }

    return Padding(
      padding: const EdgeInsets.symmetric(
          vertical: AppSpacing.xxl, horizontal: AppSpacing.lg),
      child: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(AppSpacing.md),
              decoration: BoxDecoration(
                color: bgColor,
                shape: BoxShape.circle,
              ),
              child: Icon(errorIcon, size: 36, color: iconColor),
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              displayTitle,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: AppSpacing.xs),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 320),
              child: Text(
                displayMessage,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 13,
                  color: AppColors.textSecondary,
                  height: 1.4,
                ),
              ),
            ),
            if (presentation.canRetry && onRetry != null) ...[
              const SizedBox(height: AppSpacing.md),
              AppButton(
                text: 'Try Again',
                onPressed: onRetry,
                variant: AppButtonVariant.outline,
                icon: Icons.refresh_rounded,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
