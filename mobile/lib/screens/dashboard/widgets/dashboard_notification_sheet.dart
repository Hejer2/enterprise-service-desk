import 'package:flutter/material.dart';
import '../../../models/ticket.dart';
import '../../tickets/ticket_detail_screen.dart';

class DashboardNotificationSheet extends StatelessWidget {
  final List<dynamic> notifications;

  const DashboardNotificationSheet({
    super.key,
    required this.notifications,
  });

  @override
  Widget build(BuildContext context) {
    final unreadCount =
        notifications.where((n) => !(n['isRead'] as bool? ?? true)).length;

    return SafeArea(
      child: Container(
        constraints: BoxConstraints(
          maxHeight: MediaQuery.of(context).size.height * 0.75,
        ),
        decoration: const BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
        ),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // Drag Pill Handle
            Center(
              child: Container(
                margin: const EdgeInsets.only(top: 10, bottom: 8),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
            ),

            // Top Header Bar
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 8),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Row(
                    children: [
                      const Text(
                        'Notifications',
                        style: TextStyle(
                          fontSize: 20,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF0F172A),
                          letterSpacing: -0.5,
                        ),
                      ),
                      if (unreadCount > 0) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFF2563EB),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '$unreadCount new',
                            style: const TextStyle(
                                color: Colors.white,
                                fontSize: 11,
                                fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ],
                  ),
                  IconButton(
                    icon: const Icon(Icons.close,
                        size: 20, color: Color(0xFF64748B)),
                    onPressed: () => Navigator.pop(context),
                  ),
                ],
              ),
            ),
            const Divider(height: 1, color: Color(0xFFE2E8F0)),

            // Notifications Feed Body
            if (notifications.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 48),
                child: Column(
                  children: [
                    Icon(Icons.notifications_off_outlined,
                        size: 48, color: Color(0xFF94A3B8)),
                    SizedBox(height: 12),
                    Text(
                      'No notifications yet',
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 15,
                          color: Color(0xFF475569)),
                    ),
                    SizedBox(height: 4),
                    Text(
                      'We will notify you when updates arrive.',
                      style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                    ),
                  ],
                ),
              )
            else
              Flexible(
                child: ListView.separated(
                  padding: const EdgeInsets.all(12),
                  shrinkWrap: true,
                  itemCount: notifications.length,
                  separatorBuilder: (context, index) =>
                      const SizedBox(height: 6),
                  itemBuilder: (context, index) {
                    final notif = notifications[index] as Map<String, dynamic>;
                    final title =
                        notif['title']?.toString() ?? 'Service Desk Update';
                    final message = notif['message']?.toString() ?? '';
                    final createdAt = notif['createdAt']?.toString() ?? '';
                    final isRead = notif['isRead'] as bool? ?? true;
                    final notifType =
                        notif['type']?.toString().toLowerCase() ?? 'ticket';

                    IconData typeIcon = Icons.confirmation_number_rounded;
                    Color badgeColor = const Color(0xFF2563EB);
                    if (notifType.contains('leave')) {
                      typeIcon = Icons.calendar_month_rounded;
                      badgeColor = const Color(0xFF8B5CF6);
                    } else if (notifType.contains('urgent') ||
                        notifType.contains('sla')) {
                      typeIcon = Icons.warning_amber_rounded;
                      badgeColor = const Color(0xFFEF4444);
                    }

                    int? ticketId = (notif['entityId'] as num?)?.toInt() ??
                        (notif['entity_id'] as num?)?.toInt() ??
                        (notif['relatedId'] as num?)?.toInt() ??
                        (notif['related_id'] as num?)?.toInt();

                    if (ticketId == null || ticketId <= 0) {
                      final match = RegExp(r'(?:#|TCK-|Ticket\s*#?)\s*(\d+)', caseSensitive: false)
                          .firstMatch('$title $message');
                      if (match != null) {
                        ticketId = int.tryParse(match.group(1)!);
                      }
                    }

                    return InkWell(
                      onTap: () {
                        if (ticketId != null && ticketId > 0) {
                          Navigator.pop(context);
                          final t = Ticket(
                            id: ticketId,
                            ticketNumber: 'TCK-$ticketId',
                            title: title.isNotEmpty ? title : 'Ticket #$ticketId',
                            description: message,
                            category: 'Support',
                            priority: 'Medium',
                            status: 'Open',
                            createdAt: DateTime.now(),
                            updatedAt: DateTime.now(),
                          );
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                                builder: (context) =>
                                    TicketDetailScreen(ticket: t)),
                          );
                        }
                      },
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        decoration: BoxDecoration(
                          color: isRead
                              ? Colors.white
                              : const Color(0xFF2563EB).withOpacity(0.04),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(
                            color: isRead
                                ? const Color(0xFFE2E8F0)
                                : const Color(0xFF2563EB).withOpacity(0.2),
                          ),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Stack(
                              children: [
                                Container(
                                  width: 42,
                                  height: 42,
                                  decoration: BoxDecoration(
                                    shape: BoxShape.circle,
                                    color: badgeColor.withOpacity(0.15),
                                  ),
                                  child: Icon(typeIcon,
                                      size: 20, color: badgeColor),
                                ),
                              ],
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    title,
                                    style: TextStyle(
                                      fontSize: 13.5,
                                      fontWeight: isRead
                                          ? FontWeight.w600
                                          : FontWeight.w800,
                                      color: const Color(0xFF0F172A),
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Text(
                                    message,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: TextStyle(
                                      fontSize: 12.5,
                                      height: 1.35,
                                      color: isRead
                                          ? const Color(0xFF64748B)
                                          : const Color(0xFF334155),
                                    ),
                                  ),
                                  if (createdAt.isNotEmpty) ...[
                                    const SizedBox(height: 4),
                                    Text(
                                      createdAt,
                                      style: const TextStyle(
                                          fontSize: 11,
                                          color: Color(0xFF94A3B8)),
                                    ),
                                  ],
                                ],
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),
              ),
          ],
        ),
      ),
    );
  }
}
