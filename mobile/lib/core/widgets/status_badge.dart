import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class StatusBadge extends StatelessWidget {
  final String status;

  const StatusBadge({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    Color bg = AppColors.infoLight;
    Color fg = AppColors.info;

    final lower = status.toLowerCase().replaceAll(' ', '');
    if (lower == 'open' || lower == 'approved') {
      bg = AppColors.successLight;
      fg = AppColors.success;
    } else if (lower == 'inprogress' ||
        lower == 'medium' ||
        lower == 'assigned') {
      bg = AppColors.warningLight;
      fg = AppColors.warning;
    } else if (lower == 'high' || lower == 'critical' || lower == 'rejected') {
      bg = AppColors.dangerLight;
      fg = AppColors.danger;
    } else if (lower == 'resolved' || lower == 'closed') {
      bg = const Color(0xFFE0F2FE);
      fg = const Color(0xFF0369A1);
    }

    return Container(
      padding:
          const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 3),
      decoration: BoxDecoration(
        color: bg,
        borderRadius: BorderRadius.circular(AppRadius.badge),
      ),
      child: Text(
        status.toUpperCase(),
        style: TextStyle(
          color: fg,
          fontSize: 11,
          fontWeight: FontWeight.bold,
          letterSpacing: 0.5,
        ),
      ),
    );
  }
}

class PriorityBadge extends StatelessWidget {
  final String priority;

  const PriorityBadge({super.key, required this.priority});

  @override
  Widget build(BuildContext context) {
    Color color = AppColors.success;
    final lower = priority.toLowerCase();
    if (lower == 'medium') color = AppColors.warning;
    if (lower == 'high' || lower == 'critical') color = AppColors.danger;

    return Container(
      padding:
          const EdgeInsets.symmetric(horizontal: AppSpacing.sm, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(AppRadius.badge),
        border: Border.all(color: color.withOpacity(0.3)),
      ),
      child: Text(
        priority.toUpperCase(),
        style: TextStyle(
          color: color,
          fontSize: 10,
          fontWeight: FontWeight.bold,
        ),
      ),
    );
  }
}
