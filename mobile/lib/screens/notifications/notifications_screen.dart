import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_toast.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_state.dart';
import '../../core/widgets/skeleton_loader.dart';
import '../../models/ticket.dart';
import '../../models/user_notification.dart';
import '../../providers/notification_providers.dart';
import '../tickets/ticket_detail_screen.dart';
import '../tickets/ticket_list_screen.dart';

class NotificationsScreen extends ConsumerStatefulWidget {
  const NotificationsScreen({super.key});

  @override
  ConsumerState<NotificationsScreen> createState() => _NotificationsScreenState();
}

class _NotificationsScreenState extends ConsumerState<NotificationsScreen> {
  Future<void> _markAsRead(int id) async {
    try {
      final repo = ref.read(notificationRepositoryProvider);
      await repo.markAsRead(id);
      ref.read(unreadNotificationsCountProvider.notifier).decrement();
      ref.invalidate(notificationsProvider);
      if (mounted) AppToast.showSuccess(context, 'Notification marked as read');
    } catch (e) {
      if (mounted) AppToast.showError(context, 'Failed to update notification');
    }
  }

  Future<void> _markAllAsRead() async {
    try {
      final repo = ref.read(notificationRepositoryProvider);
      await repo.markAllAsRead();
      ref.read(unreadNotificationsCountProvider.notifier).clear();
      ref.invalidate(notificationsProvider);
      if (mounted) AppToast.showSuccess(context, 'All notifications marked as read');
    } catch (e) {
      if (mounted) AppToast.showError(context, 'Failed to update notifications');
    }
  }

  void _handleNotificationTap(UserNotification item) {
    if (!item.isRead) {
      _markAsRead(item.id);
    }

    int? ticketId = item.entityId;
    if (ticketId == null || ticketId <= 0) {
      final match = RegExp(r'(?:#|TCK-|Ticket\s*#?)\s*(\d+)', caseSensitive: false)
          .firstMatch('${item.title} ${item.message}');
      if (match != null) {
        ticketId = int.tryParse(match.group(1)!);
      }
    }

    if (ticketId != null && ticketId > 0) {
      final allTickets = ref.read(ticketsProvider).valueOrNull ?? [];
      final matchingTicket = allTickets.where((t) => t.id == ticketId).firstOrNull;
      final targetTicket = matchingTicket ??
          Ticket(
            id: ticketId,
            ticketNumber: 'TCK-$ticketId',
            title: item.title.isNotEmpty ? item.title : 'Ticket #$ticketId',
            description: item.message,
            category: 'Support',
            priority: 'Medium',
            status: 'Open',
            createdAt: item.createdAt,
            updatedAt: item.createdAt,
          );

      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (_) => TicketDetailScreen(ticket: targetTicket),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final notificationsAsync = ref.watch(notificationsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          IconButton(
            icon: const Icon(Icons.done_all_rounded),
            tooltip: 'Mark all as read',
            onPressed: _markAllAsRead,
          ),
        ],
      ),
      body: notificationsAsync.when(
        loading: () => const Padding(
          padding: EdgeInsets.all(16),
          child: SkeletonLoader(width: double.infinity, height: 100),
        ),
        error: (err, stack) => ErrorState(
          title: 'Failed to load notifications',
          message: err.toString(),
          onRetry: () => ref.invalidate(notificationsProvider),
        ),
        data: (notifications) {
          if (notifications.isEmpty) {
            return EmptyState.noNotifications();
          }

          final dateFormat = DateFormat('dd MMM, HH:mm');

          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(notificationsProvider),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: notifications.length,
              separatorBuilder: (context, index) => const SizedBox(height: 10),
              itemBuilder: (context, index) {
                final item = notifications[index];
                return Semantics(
                  label: '${item.title}: ${item.message}',
                  child: InkWell(
                    onTap: () => _handleNotificationTap(item),
                    child: AppCard(
                      padding: const EdgeInsets.all(14),
                      child: Row(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: item.isRead
                                  ? Colors.grey.shade100
                                  : AppColors.primary.withOpacity(0.1),
                              shape: BoxShape.circle,
                            ),
                            child: Icon(
                              item.type.contains('sla')
                                  ? Icons.warning_amber_rounded
                                  : Icons.notifications_none_rounded,
                              size: 20,
                              color: item.isRead
                                  ? Colors.grey
                                  : AppColors.primary,
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment:
                                      MainAxisAlignment.spaceBetween,
                                  children: [
                                    Expanded(
                                      child: Text(
                                        item.title,
                                        style: TextStyle(
                                          fontWeight: item.isRead
                                              ? FontWeight.w600
                                              : FontWeight.bold,
                                          fontSize: 14,
                                          color: item.isRead
                                              ? Colors.grey.shade700
                                              : AppColors.textPrimary,
                                        ),
                                      ),
                                    ),
                                    Text(
                                      dateFormat.format(item.createdAt),
                                      style: const TextStyle(
                                          fontSize: 10, color: Colors.grey),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  item.message,
                                  style: TextStyle(
                                    fontSize: 12,
                                    color: Colors.grey.shade700,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
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
