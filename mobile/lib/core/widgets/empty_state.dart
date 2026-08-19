import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'app_button.dart';

enum EmptyStateType { noTickets, noSearchResults, noNotifications, custom }

class EmptyState extends StatelessWidget {
  final String title;
  final String message;
  final String? description;
  final IconData icon;
  final String? actionText;
  final VoidCallback? onActionPressed;
  final EmptyStateType type;

  const EmptyState({
    super.key,
    this.title = 'No Data Available',
    this.message = 'There are no items to display at this time.',
    this.description,
    this.icon = Icons.inbox_outlined,
    this.actionText,
    this.onActionPressed,
    this.type = EmptyStateType.custom,
  });

  factory EmptyState.noTickets({VoidCallback? onCreateTicket}) {
    return EmptyState(
      title: 'No tickets yet',
      message: 'Create a support request and your team will help you resolve it.',
      icon: Icons.confirmation_number_outlined,
      actionText: onCreateTicket != null ? 'Create Ticket' : null,
      onActionPressed: onCreateTicket,
      type: EmptyStateType.noTickets,
    );
  }

  factory EmptyState.noSearchResults({VoidCallback? onClearFilters}) {
    return EmptyState(
      title: 'No tickets match your filters',
      message: 'Try changing or clearing your current filter criteria.',
      icon: Icons.filter_alt_off_outlined,
      actionText: onClearFilters != null ? 'Clear Filters' : null,
      onActionPressed: onClearFilters,
      type: EmptyStateType.noSearchResults,
    );
  }

  factory EmptyState.noNotifications() {
    return const EmptyState(
      title: "You're all caught up",
      message: 'There are no new notifications at this time.',
      icon: Icons.notifications_none_outlined,
      type: EmptyStateType.noNotifications,
    );
  }

  @override
  Widget build(BuildContext context) {
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
              decoration: const BoxDecoration(
                color: AppColors.primaryLight,
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 40, color: AppColors.primary),
            ),
            const SizedBox(height: AppSpacing.md),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: AppSpacing.xs),
            ConstrainedBox(
              constraints: const BoxConstraints(maxWidth: 300),
              child: Text(
                description ?? message,
                textAlign: TextAlign.center,
                style: const TextStyle(
                  fontSize: 13,
                  color: AppColors.textSecondary,
                  height: 1.4,
                ),
              ),
            ),
            if (actionText != null && onActionPressed != null) ...[
              const SizedBox(height: AppSpacing.md),
              AppButton(
                text: actionText!,
                onPressed: onActionPressed,
                variant: AppButtonVariant.primary,
                icon: type == EmptyStateType.noTickets
                    ? Icons.add_rounded
                    : Icons.clear_rounded,
              ),
            ],
          ],
        ),
      ),
    );
  }
}
