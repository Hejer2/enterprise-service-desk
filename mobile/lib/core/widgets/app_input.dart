import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class AppInput extends StatelessWidget {
  final String? label;
  final String? placeholder;
  final TextEditingController? controller;
  final bool obscureText;
  final String? errorText;
  final String? helperText;
  final Widget? leadingIcon;
  final Widget? trailingIcon;
  final TextInputType keyboardType;
  final int maxLines;
  final bool enabled;
  final bool readOnly;
  final ValueChanged<String>? onChanged;

  const AppInput({
    super.key,
    this.label,
    this.placeholder,
    this.controller,
    this.obscureText = false,
    this.errorText,
    this.helperText,
    this.leadingIcon,
    this.trailingIcon,
    this.keyboardType = TextInputType.text,
    this.maxLines = 1,
    this.enabled = true,
    this.readOnly = false,
    this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    final surfaceColor = isDark ? AppColors.darkSurface : AppColors.surface;
    final bgColor = isDark ? AppColors.darkBgApp : AppColors.bgApp;
    final textColor = isDark ? AppColors.darkTextPrimary : AppColors.textPrimary;
    final textSecColor = isDark ? AppColors.darkTextSecondary : AppColors.textSecondary;
    final borderColor = isDark ? AppColors.darkBorder : AppColors.border;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (label != null) ...[
          Text(
            label!.toUpperCase(),
            style: TextStyle(
              fontSize: 11,
              fontWeight: FontWeight.bold,
              color: textSecColor,
              letterSpacing: 0.5,
            ),
          ),
          const SizedBox(height: AppSpacing.xs),
        ],
        TextField(
          controller: controller,
          obscureText: obscureText,
          keyboardType: keyboardType,
          maxLines: maxLines,
          enabled: enabled,
          readOnly: readOnly,
          onChanged: onChanged,
          style: TextStyle(fontSize: 14, color: textColor),
          decoration: InputDecoration(
            hintText: placeholder,
            hintStyle: TextStyle(fontSize: 14, color: textSecColor),
            filled: true,
            fillColor: enabled && !readOnly ? surfaceColor : bgColor,
            contentPadding: const EdgeInsets.symmetric(
                horizontal: AppSpacing.md, vertical: 12),
            prefixIcon: leadingIcon,
            suffixIcon: trailingIcon,
            border: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.input),
              borderSide: BorderSide(color: borderColor),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.input),
              borderSide: BorderSide(
                  color: errorText != null ? AppColors.danger : borderColor),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: BorderRadius.circular(AppRadius.input),
              borderSide: BorderSide(
                  color: errorText != null ? AppColors.danger : AppColors.primary,
                  width: 2),
            ),
            errorText: errorText,
            helperText: helperText,
          ),
        ),
      ],
    );
  }
}
