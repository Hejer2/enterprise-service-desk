import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_toast.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_state.dart';
import '../../core/widgets/skeleton_loader.dart';
import '../../models/approval_request.dart';
import '../../repositories/approval_repository.dart';
import '../../services/api_client.dart';

final approvalRepositoryProvider =
    Provider((ref) => ApprovalRepository(ref.read(apiClientProvider)));

final pendingApprovalsProvider =
    FutureProvider.autoDispose<List<ApprovalRequest>>((ref) async {
  final repo = ref.read(approvalRepositoryProvider);
  return repo.getPendingApprovals();
});

class ApprovalsScreen extends ConsumerWidget {
  const ApprovalsScreen({super.key});

  Future<void> _respond(
      BuildContext context, WidgetRef ref, int id, String action) async {
    try {
      final repo = ref.read(approvalRepositoryProvider);
      await repo.respondApproval(id, action);
      ref.invalidate(pendingApprovalsProvider);
      if (context.mounted) {
        AppToast.showSuccess(
            context, 'Approval request ${action.toLowerCase()}d.');
      }
    } catch (e) {
      if (context.mounted) {
        AppToast.showError(context, 'Failed to process approval.');
      }
    }
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final approvalsAsync = ref.watch(pendingApprovalsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Pending Approvals'),
      ),
      body: approvalsAsync.when(
        loading: () => const Padding(
          padding: EdgeInsets.all(16),
          child: SkeletonLoader(width: double.infinity, height: 120),
        ),
        error: (err, stack) => ErrorState(
          title: 'Failed to load approvals',
          message: err.toString(),
          onRetry: () => ref.invalidate(pendingApprovalsProvider),
        ),
        data: (approvals) {
          if (approvals.isEmpty) {
            return const EmptyState(
              icon: Icons.check_circle_outline_rounded,
              title: 'No Pending Approvals',
              message: 'You have no pending ticket approval requests to review.',
            );
          }

          final dateFormat = DateFormat('dd MMM yyyy, HH:mm');

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(pendingApprovalsProvider),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: approvals.length,
              separatorBuilder: (context, index) => const SizedBox(height: 12),
              itemBuilder: (context, index) {
                final item = approvals[index];
                return Semantics(
                  label:
                      'Approval Request for Ticket #${item.ticketNumber}: ${item.ticketTitle}',
                  child: AppCard(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text(
                              '#${item.ticketNumber}',
                              style: const TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: AppColors.primary,
                              ),
                            ),
                            Text(
                              dateFormat.format(item.requestedAt),
                              style: const TextStyle(
                                  fontSize: 11, color: Colors.grey),
                            ),
                          ],
                        ),
                        const SizedBox(height: 6),
                        Text(
                          item.ticketTitle,
                          style: const TextStyle(
                            fontSize: 15,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Text(
                          'Requested by: ${item.requestedBy}',
                          style: const TextStyle(
                              fontSize: 12, color: Colors.grey),
                        ),
                        if (item.reason != null && item.reason!.isNotEmpty) ...[
                          const SizedBox(height: 8),
                          Text(
                            'Reason: ${item.reason}',
                            style: const TextStyle(
                                fontSize: 12, fontStyle: FontStyle.italic),
                          ),
                        ],
                        const SizedBox(height: 14),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: [
                            OutlinedButton(
                              onPressed: () =>
                                  _respond(context, ref, item.id, 'REJECT'),
                              style: OutlinedButton.styleFrom(
                                foregroundColor: AppColors.danger,
                              ),
                              child: const Text('Reject'),
                            ),
                            const SizedBox(width: 12),
                            ElevatedButton(
                              onPressed: () =>
                                  _respond(context, ref, item.id, 'APPROVE'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF059669),
                                foregroundColor: Colors.white,
                              ),
                              child: const Text('Approve'),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
