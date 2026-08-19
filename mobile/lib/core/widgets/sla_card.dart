import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../models/ticket_sla.dart';
import 'app_card.dart';
import 'sla_status_badge.dart';

class SlaCard extends StatelessWidget {
  final TicketSla sla;

  const SlaCard({super.key, required this.sla});

  String _formatRemainingTime(int totalMinutes) {
    if (totalMinutes <= 0) return '0m remaining';
    final hours = totalMinutes ~/ 60;
    final mins = totalMinutes % 60;
    if (hours > 0) {
      return '${hours}h ${mins}m remaining';
    }
    return '${mins}m remaining';
  }

  @override
  Widget build(BuildContext context) {
    final dateFormat = DateFormat('d MMMM yyyy HH:mm');
    final formattedDue = sla.resolutionDueAt != null
        ? dateFormat.format(sla.resolutionDueAt!)
        : 'N/A';

    final remainingText = _formatRemainingTime(sla.remainingMinutes);

    return Semantics(
      label: 'SLA Tracking Card. Resolution SLA ${sla.resolutionStatus}, $remainingText, Due $formattedDue',
      child: AppCard(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'SLA Tracking',
                  style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                SlaStatusBadge(status: sla.status),
              ],
            ),
            const Divider(height: 24),
            
            // First Response SLA
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'First Response',
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                Text(
                  sla.firstResponseStatus,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: sla.firstResponseStatus == 'COMPLETED'
                        ? const Color(0xFF15803D)
                        : (sla.firstResponseStatus == 'BREACHED'
                            ? const Color(0xFFB91C1C)
                            : const Color(0xFF2563EB)),
                  ),
                ),
              ],
            ),
            if (sla.firstResponseDueAt != null) ...[
              const SizedBox(height: 2),
              Text(
                'Due: ${dateFormat.format(sla.firstResponseDueAt!)}',
                style: const TextStyle(fontSize: 11, color: Colors.grey),
              ),
            ],
            const SizedBox(height: 12),

            // Resolution SLA
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Resolution SLA',
                  style: TextStyle(
                    fontSize: 13,
                    color: Colors.grey,
                    fontWeight: FontWeight.w500,
                  ),
                ),
                Text(
                  remainingText,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF1E293B),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 2),
            Text(
              'Deadline: $formattedDue',
              style: const TextStyle(fontSize: 11, color: Colors.grey),
            ),
          ],
        ),
      ),
    );
  }
}
