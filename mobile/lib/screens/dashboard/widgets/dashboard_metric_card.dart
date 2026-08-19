import 'package:flutter/material.dart';
import '../../../core/theme/app_theme.dart';
import '../../../core/widgets/app_card.dart';

class DashboardMetricCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final bool isDesktop;

  const DashboardMetricCard({
    super.key,
    required this.title,
    required this.value,
    required this.icon,
    this.isDesktop = true,
  });

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: EdgeInsets.all(isDesktop ? AppSpacing.lg : AppSpacing.md),
      child: Stack(
        children: [
          Positioned(
            right: 0,
            top: 0,
            child: Container(
              padding: const EdgeInsets.all(AppSpacing.sm),
              decoration: BoxDecoration(
                color: AppColors.primaryLight,
                borderRadius: BorderRadius.circular(AppRadius.input),
              ),
              child: Icon(icon, color: AppColors.primary, size: isDesktop ? 22 : 18),
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title.toUpperCase(),
                style: TextStyle(
                  fontSize: isDesktop ? 12 : 10,
                  fontWeight: FontWeight.bold,
                  color: AppColors.textSecondary,
                  letterSpacing: 0.5,
                ),
              ),
              const SizedBox(height: AppSpacing.xs),
              Text(
                value,
                style: TextStyle(
                  fontSize: isDesktop ? 28 : 22,
                  fontWeight: FontWeight.w800,
                  color: AppColors.textPrimary,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}
