import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'app_button.dart';

Future<bool> showAppConfirmDialog(
  BuildContext context, {
  required String title,
  required String message,
  String confirmText = 'Confirm',
  String cancelText = 'Cancel',
  bool isDestructive = false,
  IconData icon = Icons.warning_amber_rounded,
}) async {
  final result = await showDialog<bool>(
    context: context,
    barrierDismissible: true,
    builder: (dialogContext) {
      return Dialog(
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(AppRadius.card),
        ),
        elevation: 8,
        backgroundColor: AppColors.surface,
        child: Padding(
          padding: const EdgeInsets.all(AppSpacing.lg),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(AppSpacing.sm),
                    decoration: BoxDecoration(
                      color: isDestructive
                          ? AppColors.dangerLight
                          : AppColors.primaryLight,
                      shape: BoxShape.circle,
                    ),
                    child: Icon(
                      icon,
                      color: isDestructive
                          ? AppColors.danger
                          : AppColors.primary,
                      size: 22,
                    ),
                  ),
                  const SizedBox(width: AppSpacing.md),
                  Expanded(
                    child: Text(
                      title,
                      style: const TextStyle(
                        fontSize: 17,
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: AppSpacing.md),
              Text(
                message,
                style: const TextStyle(
                  fontSize: 14,
                  color: AppColors.textSecondary,
                  height: 1.4,
                ),
              ),
              const SizedBox(height: AppSpacing.lg),
              Row(
                mainAxisAlignment: MainAxisAlignment.end,
                children: [
                  AppButton(
                    text: cancelText,
                    variant: AppButtonVariant.outline,
                    onPressed: () => Navigator.of(dialogContext).pop(false),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                  AppButton(
                    text: confirmText,
                    variant: isDestructive
                        ? AppButtonVariant.danger
                        : AppButtonVariant.primary,
                    onPressed: () => Navigator.of(dialogContext).pop(true),
                  ),
                ],
              ),
            ],
          ),
        ),
      );
    },
  );
  return result ?? false;
}
