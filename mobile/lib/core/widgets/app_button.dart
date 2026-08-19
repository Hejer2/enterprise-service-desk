import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

enum AppButtonVariant { primary, secondary, outline, ghost, danger }

class AppButton extends StatefulWidget {
  final String text;
  final VoidCallback? onPressed;
  final AppButtonVariant variant;
  final IconData? icon;
  final bool isLoading;
  final bool isFullWidth;
  final double height;

  const AppButton({
    super.key,
    required this.text,
    this.onPressed,
    this.variant = AppButtonVariant.primary,
    this.icon,
    this.isLoading = false,
    this.isFullWidth = false,
    this.height = 44.0,
  });

  @override
  State<AppButton> createState() => _AppButtonState();
}

class _AppButtonState extends State<AppButton> {
  bool _isPressed = false;

  @override
  Widget build(BuildContext context) {
    Color bg = AppColors.primary;
    Color fg = Colors.white;
    BorderSide border = BorderSide.none;

    switch (widget.variant) {
      case AppButtonVariant.primary:
        bg = AppColors.primary;
        fg = Colors.white;
        break;
      case AppButtonVariant.secondary:
        bg = AppColors.primaryLight;
        fg = AppColors.primary;
        break;
      case AppButtonVariant.outline:
        bg = Colors.transparent;
        fg = AppColors.textPrimary;
        border = const BorderSide(color: AppColors.borderStrong);
        break;
      case AppButtonVariant.ghost:
        bg = Colors.transparent;
        fg = AppColors.textPrimary;
        break;
      case AppButtonVariant.danger:
        bg = AppColors.danger;
        fg = Colors.white;
        break;
    }

    final isEnabled = widget.onPressed != null && !widget.isLoading;

    return AnimatedScale(
      scale: _isPressed ? 0.98 : 1.0,
      duration: const Duration(milliseconds: 150),
      child: SizedBox(
        height: widget.height,
        width: widget.isFullWidth ? double.infinity : null,
        child: GestureDetector(
          onTapDown:
              isEnabled ? (_) => setState(() => _isPressed = true) : null,
          onTapUp: isEnabled ? (_) => setState(() => _isPressed = false) : null,
          onTapCancel:
              isEnabled ? () => setState(() => _isPressed = false) : null,
          child: ElevatedButton(
            onPressed: isEnabled ? widget.onPressed : null,
            style: ElevatedButton.styleFrom(
              backgroundColor: bg,
              foregroundColor: fg,
              disabledBackgroundColor: bg.withOpacity(0.6),
              disabledForegroundColor: fg.withOpacity(0.7),
              elevation: 0,
              shadowColor: Colors.transparent,
              side: border,
              padding: const EdgeInsets.symmetric(horizontal: AppSpacing.md),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(AppRadius.button),
              ),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                if (widget.isLoading) ...[
                  SizedBox(
                    width: 16,
                    height: 16,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(fg),
                    ),
                  ),
                  const SizedBox(width: AppSpacing.sm),
                ] else if (widget.icon != null) ...[
                  Icon(widget.icon, size: 16, color: fg),
                  const SizedBox(width: AppSpacing.sm),
                ],
                Text(
                  widget.text,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.w600,
                    color: fg,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
