import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/ticket.dart';
import '../theme/app_theme.dart';
import 'status_badge.dart';

class TicketCard extends StatelessWidget {
  final Ticket ticket;
  final VoidCallback onTap;

  const TicketCard({
    super.key,
    required this.ticket,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(AppRadius.card),
      child: Container(
        padding: const EdgeInsets.all(AppSpacing.md),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(AppRadius.card),
          border: Border.all(color: AppColors.border),
          boxShadow: AppShadows.small,
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: AppSpacing.sm, vertical: 3),
                      decoration: BoxDecoration(
                        color: AppColors.primaryLight,
                        borderRadius: BorderRadius.circular(AppRadius.badge),
                      ),
                      child: Text(
                        ticket.ticketNumber,
                        style: const TextStyle(
                          color: AppColors.primary,
                          fontWeight: FontWeight.bold,
                          fontSize: 12,
                        ),
                      ),
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    PriorityBadge(priority: ticket.priority),
                  ],
                ),
                StatusBadge(status: ticket.status),
              ],
            ),
            const SizedBox(height: AppSpacing.sm),
            Text(
              ticket.title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(
                fontSize: 15,
                fontWeight: FontWeight.w600,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: AppSpacing.sm),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Text(
                  'Created ${DateFormat('dd MMM, HH:mm').format(ticket.createdAt)}',
                  style: const TextStyle(
                      fontSize: 11.5, color: AppColors.textSecondary),
                ),
                if (ticket.assignedTo != null && ticket.assignedTo!.isNotEmpty)
                  Row(
                    children: [
                      const Icon(Icons.person_outline,
                          size: 12, color: AppColors.textSecondary),
                      const SizedBox(width: 3),
                      Text(
                        ticket.assignedTo!,
                        style: const TextStyle(
                            fontSize: 11.5,
                            fontWeight: FontWeight.w500,
                            color: AppColors.textSecondary),
                      ),
                    ],
                  ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}
