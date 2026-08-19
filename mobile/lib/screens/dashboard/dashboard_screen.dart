import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/status_badge.dart';
import '../../models/ticket.dart';
import '../../services/api_client.dart';
import '../../repositories/dashboard_repository.dart';
import '../tickets/ticket_detail_screen.dart';
import 'widgets/dashboard_metric_card.dart';

final dashboardRepositoryProvider =
    Provider((ref) => DashboardRepository(ref.read(apiClientProvider)));

final dashboardStatsProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  return ref.read(dashboardRepositoryProvider).getDashboardStats();
});

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDesktop = MediaQuery.of(context).size.width >= 768;
    final statsAsync = ref.watch(dashboardStatsProvider);

    return SingleChildScrollView(
      padding: const EdgeInsets.only(
        left: 16,
        right: 16,
        top: 16,
        bottom: 24,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          statsAsync.when(
            data: (stats) {
              if (stats.isEmpty) {
                return const Center(child: Text('No data available.'));
              }

              final type = stats['type'] ?? 'admin';

              if (type == 'hr') {
                return _buildHrDashboard(context, stats, isDesktop, theme);
              } else if (type == 'it' || type == 'maintenance') {
                return _buildTechnicianDashboard(
                    context, stats, isDesktop, theme, type);
              } else if (type == 'employee') {
                return _buildEmployeeDashboard(
                    context, stats, isDesktop, theme);
              } else {
                return _buildAdminDashboard(context, stats, isDesktop, theme);
              }
            },
            loading: () => const Center(child: CircularProgressIndicator()),
            error: (err, stack) => Center(
                child: Text('Error: $err',
                    style: const TextStyle(color: AppColors.danger))),
          ),
        ],
      ),
    );
  }

  Widget _buildTechnicianDashboard(
      BuildContext context,
      Map<String, dynamic> stats,
      bool isDesktop,
      ThemeData theme,
      String type) {
    final assigned = stats['assignedTickets'].toString();
    final resolved = stats['totalResolved'].toString();
    final List<dynamic> warnings = stats['slaWarnings'] ?? [];
    final slaWarningsCount = warnings.length.toString();

    final title =
        type == 'it' ? 'IT Operations Dashboard' : 'Maintenance Dashboard';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontSize: isDesktop ? 30 : 22,
                      fontWeight: FontWeight.w800,
                      color: theme.colorScheme.onSurface,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Manage your assigned tickets and SLA targets.',
                    style: TextStyle(
                      color: theme.colorScheme.onSurface.withOpacity(0.6),
                      fontSize: isDesktop ? 15 : 13,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            _buildNotificationBell(context, stats['notifications'] ?? []),
          ],
        ),
        const SizedBox(height: 20),
        if (isDesktop)
          Row(
            children: [
              Expanded(
                  child: _buildMetricCard(context, 'Assigned Tickets', assigned,
                      Icons.build_outlined,
                      isDesktop: true)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _buildMetricCard(context, 'SLA Warnings',
                      slaWarningsCount, Icons.warning_amber_rounded,
                      isDesktop: true)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _buildMetricCard(context, 'Total Resolved', resolved,
                      Icons.emoji_events_outlined,
                      isDesktop: true)),
            ],
          )
        else
          Row(
            children: [
              Expanded(
                  child: _buildMetricCard(
                      context, 'Assigned', assigned, Icons.build_outlined,
                      isDesktop: false)),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: _buildMetricCard(context, 'Warnings', slaWarningsCount,
                      Icons.warning_amber_rounded,
                      isDesktop: false)),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: _buildMetricCard(context, 'Resolved', resolved,
                      Icons.emoji_events_outlined,
                      isDesktop: false)),
            ],
          ),
        const SizedBox(height: 24),
        AppCard(
          padding: EdgeInsets.all(isDesktop ? 24 : 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('My Worklist',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),
              const Divider(),
              const SizedBox(height: 12),
              if (stats['assignedTicketsList'] == null ||
                  (stats['assignedTicketsList'] as List).isEmpty)
                const Text('Great job! You have no assigned active tickets.',
                    style: TextStyle(color: AppColors.textSecondary))
              else if (!isDesktop)
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: (stats['assignedTicketsList'] as List).length,
                  separatorBuilder: (context, index) =>
                      const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final req = (stats['assignedTicketsList'] as List)[index]
                        as Map<String, dynamic>;
                    final ticketNumber = req['ticketNumber']?.toString() ?? '';
                    final title = req['title']?.toString() ?? '';
                    final status = req['status']?.toString() ?? '';
                    final priority = req['priority']?.toString() ?? 'Medium';
                    final ticketId = req['id'] as int?;

                    return InkWell(
                      onTap: () {
                        if (ticketId != null) {
                          final t = Ticket(
                            id: ticketId,
                            ticketNumber: ticketNumber,
                            title: title,
                            description: '',
                            category: 'Support',
                            priority: priority,
                            status: status,
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
                          color: theme.colorScheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(ticketNumber,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          color: Color(0xFF6366F1),
                                          fontSize: 12.5)),
                                  const SizedBox(height: 2),
                                  Text(title,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.w600,
                                          fontSize: 13.5),
                                      overflow: TextOverflow.ellipsis),
                                  const SizedBox(height: 4),
                                  Text('Priority: $priority',
                                      style: const TextStyle(
                                          fontSize: 11,
                                          color: Color(0xFF64748B))),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            Container(
                              padding: const EdgeInsets.symmetric(
                                  horizontal: 8, vertical: 4),
                              decoration: BoxDecoration(
                                color:
                                    const Color(0xFF6366F1).withOpacity(0.12),
                                borderRadius: BorderRadius.circular(6),
                              ),
                              child: Text(
                                status,
                                style: const TextStyle(
                                    color: Color(0xFF6366F1),
                                    fontWeight: FontWeight.bold,
                                    fontSize: 11),
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                )
              else
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: DataTable(
                    headingTextStyle: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary),
                    columns: const [
                      DataColumn(label: Text('Number')),
                      DataColumn(label: Text('Title')),
                      DataColumn(label: Text('Status')),
                      DataColumn(label: Text('Priority')),
                    ],
                    rows: (stats['assignedTicketsList'] as List).map((req) {
                      return DataRow(
                        cells: [
                          DataCell(Text(req['ticketNumber'] ?? '',
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF6366F1)))),
                          DataCell(SizedBox(
                              width: 200,
                              child: Text(req['title'] ?? '',
                                  overflow: TextOverflow.ellipsis))),
                          DataCell(Text(req['status'] ?? '')),
                          DataCell(Text(req['priority'] ?? '')),
                        ],
                      );
                    }).toList(),
                  ),
                ),
            ],
          ),
        ),
        const SizedBox(height: 20),
        AppCard(
          padding: EdgeInsets.all(isDesktop ? 24 : 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Urgent & Critical Actions',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),
              const Divider(),
              const SizedBox(height: 12),
              if (stats['urgentTicketsList'] == null ||
                  (stats['urgentTicketsList'] as List).isEmpty)
                const Text('No high-priority active tickets assigned to you.',
                    style: TextStyle(color: AppColors.textSecondary))
              else if (!isDesktop)
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: (stats['urgentTicketsList'] as List).length,
                  separatorBuilder: (context, index) =>
                      const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final req = (stats['urgentTicketsList'] as List)[index]
                        as Map<String, dynamic>;
                    final ticketNumber = req['ticketNumber']?.toString() ?? '';
                    final title = req['title']?.toString() ?? '';
                    final status = req['status']?.toString() ?? '';
                    final priority = req['priority']?.toString() ?? 'Critical';
                    final ticketId = req['id'] as int?;

                    return InkWell(
                      onTap: () {
                        if (ticketId != null) {
                          final t = Ticket(
                            id: ticketId,
                            ticketNumber: ticketNumber,
                            title: title,
                            description: '',
                            category: 'Support',
                            priority: priority,
                            status: status,
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
                          color: theme.colorScheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border:
                              Border.all(color: Colors.red.withOpacity(0.3)),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    children: [
                                      Text(ticketNumber,
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold,
                                              color: Color(0xFF6366F1),
                                              fontSize: 12.5)),
                                      const SizedBox(width: 8),
                                      PriorityBadge(priority: priority),
                                    ],
                                  ),
                                  const SizedBox(height: 4),
                                  Text(title,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.w600,
                                          fontSize: 13.5),
                                      overflow: TextOverflow.ellipsis),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            StatusBadge(status: status),
                          ],
                        ),
                      ),
                    );
                  },
                )
              else
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: DataTable(
                    headingTextStyle: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary),
                    columns: const [
                      DataColumn(label: Text('Number')),
                      DataColumn(label: Text('Title')),
                      DataColumn(label: Text('Priority')),
                      DataColumn(label: Text('Status')),
                    ],
                    rows: (stats['urgentTicketsList'] as List).map((req) {
                      return DataRow(
                        cells: [
                          DataCell(Text(req['ticketNumber'] ?? '',
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF6366F1)))),
                          DataCell(SizedBox(
                              width: 200,
                              child: Text(req['title'] ?? '',
                                  overflow: TextOverflow.ellipsis))),
                          DataCell(PriorityBadge(
                              priority: req['priority'] ?? 'Low')),
                          DataCell(Text(req['status'] ?? '')),
                        ],
                      );
                    }).toList(),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildEmployeeDashboard(BuildContext context,
      Map<String, dynamic> stats, bool isDesktop, ThemeData theme) {
    final open = stats['openTickets'].toString();
    final inProgress = stats['inProgressTickets'].toString();
    final resolved = stats['resolvedTickets'].toString();
    final List<dynamic> notifications = stats['notifications'] ?? [];
    final List<dynamic> activeTickets = stats['activeTicketsList'] ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Employee Service Dashboard',
                    style: TextStyle(
                      fontSize: isDesktop ? 30 : 22,
                      fontWeight: FontWeight.w800,
                      color: theme.colorScheme.onSurface,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Track your requested services and IT support tickets.',
                    style: TextStyle(
                      color: theme.colorScheme.onSurface.withOpacity(0.6),
                      fontSize: isDesktop ? 15 : 13,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            _buildNotificationBell(context, notifications),
          ],
        ),
        const SizedBox(height: 20),
        if (isDesktop)
          Row(
            children: [
              Expanded(
                  child: _buildMetricCard(
                      context, 'Open Tickets', open, Icons.access_time_rounded,
                      isDesktop: true)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _buildMetricCard(context, 'In Progress', inProgress,
                      Icons.hourglass_top_rounded,
                      isDesktop: true)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _buildMetricCard(context, 'Resolved', resolved,
                      Icons.check_circle_outline_rounded,
                      isDesktop: true)),
            ],
          )
        else
          Row(
            children: [
              Expanded(
                  child: _buildMetricCard(
                      context, 'Open', open, Icons.access_time_rounded,
                      isDesktop: false)),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: _buildMetricCard(context, 'Progress', inProgress,
                      Icons.hourglass_top_rounded,
                      isDesktop: false)),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: _buildMetricCard(context, 'Resolved', resolved,
                      Icons.check_circle_outline_rounded,
                      isDesktop: false)),
            ],
          ),
        const SizedBox(height: 24),
        AppCard(
          padding: EdgeInsets.all(isDesktop ? 24 : 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('My Active Tickets',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
              const SizedBox(height: 12),
              const Divider(),
              const SizedBox(height: 12),
              if (activeTickets.isEmpty)
                const Text('You have no active tickets.',
                    style: TextStyle(color: AppColors.textSecondary))
              else if (!isDesktop)
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: activeTickets.length,
                  separatorBuilder: (context, index) =>
                      const SizedBox(height: 10),
                  itemBuilder: (context, index) {
                    final req = activeTickets[index] as Map<String, dynamic>;
                    final ticketNumber = req['ticketNumber']?.toString() ?? '';
                    final title = req['title']?.toString() ?? '';
                    final status = req['status']?.toString() ?? '';
                    final priority = req['priority']?.toString() ?? 'Medium';
                    final ticketId = req['id'] as int?;

                    return InkWell(
                      onTap: () {
                        if (ticketId != null) {
                          final t = Ticket(
                            id: ticketId,
                            ticketNumber: ticketNumber,
                            title: title,
                            description: '',
                            category: 'Support',
                            priority: priority,
                            status: status,
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
                          color: theme.colorScheme.surface,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(ticketNumber,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.bold,
                                          color: Color(0xFF6366F1),
                                          fontSize: 12.5)),
                                  const SizedBox(height: 2),
                                  Text(title,
                                      style: const TextStyle(
                                          fontWeight: FontWeight.w600,
                                          fontSize: 13.5),
                                      overflow: TextOverflow.ellipsis),
                                ],
                              ),
                            ),
                            const SizedBox(width: 8),
                            StatusBadge(status: status),
                          ],
                        ),
                      ),
                    );
                  },
                )
              else
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  child: DataTable(
                    headingTextStyle: const TextStyle(
                        fontWeight: FontWeight.bold,
                        color: AppColors.textPrimary),
                    columns: const [
                      DataColumn(label: Text('Number')),
                      DataColumn(label: Text('Title')),
                      DataColumn(label: Text('Status')),
                    ],
                    rows: activeTickets.map((req) {
                      return DataRow(
                        cells: [
                          DataCell(Text(req['ticketNumber'] ?? '',
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF6366F1)))),
                          DataCell(SizedBox(
                              width: 150,
                              child: Text(req['title'] ?? '',
                                  overflow: TextOverflow.ellipsis))),
                          DataCell(Text(req['status'] ?? '')),
                        ],
                      );
                    }).toList(),
                  ),
                ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildNotificationBell(
      BuildContext context, List<dynamic> notifications) {
    return const SizedBox.shrink();
  }

  Widget _buildHrDashboard(BuildContext context, Map<String, dynamic> stats,
      bool isDesktop, ThemeData theme) {
    final pending = stats['pendingLeave'].toString();
    final approved = stats['approvedLeave'].toString();
    final rejected = stats['rejectedLeave'].toString();
    final List<dynamic> requests = stats['leaveRequests'] ?? [];

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'HR Leave Dashboard',
                    style: TextStyle(
                      fontSize: isDesktop ? 30 : 22,
                      fontWeight: FontWeight.w800,
                      color: theme.colorScheme.onSurface,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Manage leave requests and employee calendars.',
                    style: TextStyle(
                      color: theme.colorScheme.onSurface.withOpacity(0.6),
                      fontSize: isDesktop ? 15 : 13,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            _buildNotificationBell(context, stats['notifications'] ?? []),
          ],
        ),
        const SizedBox(height: 20),
        if (isDesktop)
          Row(
            children: [
              Expanded(
                  child: _buildMetricCard(context, 'Pending Requests', pending,
                      Icons.pending_actions_rounded,
                      isDesktop: true)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _buildMetricCard(context, 'Approved Leave', approved,
                      Icons.check_circle_outline_rounded,
                      isDesktop: true)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _buildMetricCard(context, 'Rejected Leave', rejected,
                      Icons.cancel_outlined,
                      isDesktop: true)),
            ],
          )
        else
          Row(
            children: [
              Expanded(
                  child: _buildMetricCard(context, 'Pending', pending,
                      Icons.pending_actions_rounded,
                      isDesktop: false)),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: _buildMetricCard(context, 'Approved', approved,
                      Icons.check_circle_outline_rounded,
                      isDesktop: false)),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: _buildMetricCard(
                      context, 'Rejected', rejected, Icons.cancel_outlined,
                      isDesktop: false)),
            ],
          ),
        const SizedBox(height: 24),
        _buildLeaveCalendarPanel(context, requests, isDesktop, theme),
      ],
    );
  }

  Widget _buildLeaveCalendarPanel(BuildContext context, List<dynamic> requests,
      bool isDesktop, ThemeData theme) {
    if (!isDesktop) {
      // Mobile Leave Request Cards
      return AppCard(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text('📅 Leave Registry',
                    style:
                        TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                Container(
                  padding:
                      const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                  decoration: BoxDecoration(
                    color: const Color(0xFF6366F1).withOpacity(0.1),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    '${requests.length} requests',
                    style: const TextStyle(
                        color: Color(0xFF6366F1),
                        fontSize: 11,
                        fontWeight: FontWeight.bold),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 14),
            if (requests.isEmpty)
              const Center(
                  child: Padding(
                      padding: EdgeInsets.all(24),
                      child: Text('No upcoming leave requests.',
                          style: TextStyle(color: Colors.grey))))
            else
              ListView.separated(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                itemCount: requests.length,
                separatorBuilder: (context, index) =>
                    const SizedBox(height: 10),
                itemBuilder: (context, index) {
                  final req = requests[index] as Map<String, dynamic>;
                  final employeeName =
                      req['employeeName']?.toString() ?? 'Employee';
                  final type = req['type']?.toString() ?? 'Leave';
                  final startDate = req['startDate']?.toString() ?? '';
                  final endDate = req['endDate']?.toString() ?? '';
                  final duration = req['duration']?.toString() ?? '';
                  final ticketNumber = req['ticketNumber']?.toString() ?? '';
                  final status = req['status']?.toString() ?? 'Pending';
                  final initial = employeeName.isNotEmpty
                      ? employeeName.substring(0, 1)
                      : 'E';

                  Color statusBgColor = Colors.orange.withOpacity(0.12);
                  Color statusTextColor = Colors.orange.shade800;
                  if (status.toLowerCase() == 'approved') {
                    statusBgColor = Colors.green.withOpacity(0.12);
                    statusTextColor = Colors.green.shade800;
                  } else if (status.toLowerCase() == 'rejected') {
                    statusBgColor = Colors.red.withOpacity(0.12);
                    statusTextColor = Colors.red.shade800;
                  }

                  final ticketId = req['ticketId'] as int?;

                  return InkWell(
                    onTap: () {
                      if (ticketId != null) {
                        final t = Ticket(
                          id: ticketId,
                          ticketNumber: ticketNumber,
                          title: '$employeeName - $type',
                          description: '',
                          category: 'Leave Request',
                          priority: 'Medium',
                          status: status,
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
                        color: theme.colorScheme.surface,
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: Colors.grey.withOpacity(0.2)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              CircleAvatar(
                                radius: 14,
                                backgroundColor:
                                    const Color(0xFF6366F1).withOpacity(0.12),
                                child: Text(initial,
                                    style: const TextStyle(
                                        color: Color(0xFF6366F1),
                                        fontWeight: FontWeight.bold,
                                        fontSize: 11)),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(employeeName,
                                    style: const TextStyle(
                                        fontWeight: FontWeight.bold,
                                        fontSize: 13.5)),
                              ),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                    horizontal: 8, vertical: 3),
                                decoration: BoxDecoration(
                                    color: statusBgColor,
                                    borderRadius: BorderRadius.circular(8)),
                                child: Text(status.toUpperCase(),
                                    style: TextStyle(
                                        color: statusTextColor,
                                        fontSize: 9.5,
                                        fontWeight: FontWeight.w800)),
                              ),
                            ],
                          ),
                          const SizedBox(height: 8),
                          Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              Text(type,
                                  style: const TextStyle(
                                      fontSize: 12,
                                      fontWeight: FontWeight.w600,
                                      color: Color(0xFF475569))),
                              if (ticketNumber.isNotEmpty)
                                Text(ticketNumber,
                                    style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.w800,
                                        color: Color(0xFF6366F1))),
                            ],
                          ),
                          const SizedBox(height: 4),
                          Row(
                            children: [
                              Icon(Icons.calendar_today,
                                  size: 12, color: Colors.grey.shade500),
                              const SizedBox(width: 4),
                              Text('$startDate → $endDate',
                                  style: TextStyle(
                                      fontSize: 11.5,
                                      color: Colors.grey.shade600)),
                              if (duration.isNotEmpty) ...[
                                const Spacer(),
                                Text(duration,
                                    style: const TextStyle(
                                        fontSize: 11,
                                        fontWeight: FontWeight.bold,
                                        color: Colors.grey)),
                              ],
                            ],
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
          ],
        ),
      );
    }

    return AppCard(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Leave Registry & Calendar',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const Divider(height: 32),
          if (requests.isEmpty)
            const Text('No upcoming leave requests.')
          else
            SingleChildScrollView(
              scrollDirection: Axis.horizontal,
              child: DataTable(
                headingTextStyle: const TextStyle(
                    fontWeight: FontWeight.bold, color: Colors.black87),
                columns: const [
                  DataColumn(label: Text('Employee')),
                  DataColumn(label: Text('Leave Type')),
                  DataColumn(label: Text('Start Date')),
                  DataColumn(label: Text('End Date')),
                  DataColumn(label: Text('Duration')),
                  DataColumn(label: Text('Ticket')),
                  DataColumn(label: Text('Status')),
                ],
                rows: requests.map((req) {
                  return DataRow(
                    cells: [
                      DataCell(Text(req['employeeName'] ?? '',
                          style: const TextStyle(fontWeight: FontWeight.w600))),
                      DataCell(Text(req['type'] ?? '')),
                      DataCell(Text(req['startDate'] ?? '')),
                      DataCell(Text(req['endDate'] ?? '')),
                      DataCell(Text(req['duration'] ?? '')),
                      DataCell(
                        Text(
                          req['ticketNumber'] ?? '',
                          style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF6366F1)),
                        ),
                      ),
                      DataCell(
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: req['status'] == 'Approved'
                                ? Colors.green.withOpacity(0.1)
                                : (req['status'] == 'Rejected'
                                    ? Colors.red.withOpacity(0.1)
                                    : Colors.orange.withOpacity(0.1)),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            req['status'] ?? '',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: req['status'] == 'Approved'
                                  ? Colors.green
                                  : (req['status'] == 'Rejected'
                                      ? Colors.red
                                      : Colors.orange),
                            ),
                          ),
                        ),
                      ),
                    ],
                  );
                }).toList(),
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildAdminDashboard(BuildContext context, Map<String, dynamic> stats,
      bool isDesktop, ThemeData theme) {
    final totalUsers = stats['totalUsers'].toString();
    final totalTickets = stats['totalTickets'].toString();
    final avgRes = '${stats['avgResolutionTime']} hrs';

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Admin Control Dashboard',
                    style: TextStyle(
                      fontSize: isDesktop ? 30 : 22,
                      fontWeight: FontWeight.w800,
                      color: theme.colorScheme.onSurface,
                    ),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'Global view of company service desk operations.',
                    style: TextStyle(
                      color: theme.colorScheme.onSurface.withOpacity(0.6),
                      fontSize: isDesktop ? 15 : 13,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            _buildNotificationBell(context, stats['notifications'] ?? []),
          ],
        ),
        const SizedBox(height: 20),
        if (isDesktop)
          Row(
            children: [
              Expanded(
                  child: _buildMetricCard(context, 'Registered Users',
                      totalUsers, Icons.people_outline_rounded,
                      isDesktop: true)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _buildMetricCard(context, 'Total Tickets Logged',
                      totalTickets, Icons.confirmation_number_outlined,
                      isDesktop: true)),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                  child: _buildMetricCard(context, 'Avg. Resolution Time',
                      avgRes, Icons.timer_outlined,
                      isDesktop: true)),
            ],
          )
        else
          Row(
            children: [
              Expanded(
                  child: _buildMetricCard(context, 'Users', totalUsers,
                      Icons.people_outline_rounded,
                      isDesktop: false)),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: _buildMetricCard(context, 'Tickets', totalTickets,
                      Icons.confirmation_number_outlined,
                      isDesktop: false)),
              const SizedBox(width: AppSpacing.sm),
              Expanded(
                  child: _buildMetricCard(
                      context, 'Avg Time', avgRes, Icons.timer_outlined,
                      isDesktop: false)),
            ],
          ),
        const SizedBox(height: 24),
        if (isDesktop)
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Expanded(
                  flex: 1,
                  child: _buildCategoriesPanel(context, stats['categories'])),
              const SizedBox(width: 24),
              Expanded(
                  flex: 2,
                  child: _buildWorkloadPanel(
                      context, stats['workloads'], isDesktop)),
            ],
          )
        else
          Column(
            children: [
              _buildCategoriesPanel(context, stats['categories']),
              const SizedBox(height: 16),
              _buildWorkloadPanel(context, stats['workloads'], isDesktop),
            ],
          ),
      ],
    );
  }

  Widget _buildMetricCard(
      BuildContext context, String title, String value, IconData icon,
      {bool isDesktop = true}) {
    return DashboardMetricCard(
      title: title,
      value: value,
      icon: icon,
      isDesktop: isDesktop,
    );
  }

  Widget _buildCategoriesPanel(BuildContext context, List<dynamic> categories) {
    return AppCard(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('Tickets by Category',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
          const SizedBox(height: 16),
          ...categories.map((cat) {
            return Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(cat['name'] as String,
                          style: const TextStyle(
                              fontSize: 13, fontWeight: FontWeight.w500)),
                      Text('${cat['count']}',
                          style: const TextStyle(
                              fontWeight: FontWeight.bold, fontSize: 13)),
                    ],
                  ),
                  const SizedBox(height: 6),
                  Container(
                    height: 6,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      color: Theme.of(context).dividerColor,
                      borderRadius: BorderRadius.circular(3),
                    ),
                    child: FractionallySizedBox(
                      alignment: Alignment.centerLeft,
                      widthFactor: (cat['pct'] as int) / 100,
                      child: Container(
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                              colors: [Color(0xFF6366F1), Color(0xFF8B5CF6)]),
                          borderRadius: BorderRadius.circular(3),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  Widget _buildWorkloadPanel(
      BuildContext context, List<dynamic> workloads, bool isDesktop) {
    final theme = Theme.of(context);

    if (!isDesktop) {
      // Mobile Workload Cards
      return AppCard(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Technician Workloads',
                style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 14),
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: workloads.length,
              separatorBuilder: (context, index) => const Divider(height: 16),
              itemBuilder: (context, index) {
                final w = workloads[index];
                final active = w['active'] as int;
                Color bgColor;
                Color textColor;
                String badgeText;

                if (active > 5) {
                  bgColor = const Color(0xFFFEE2E2);
                  textColor = const Color(0xFFB91C1C);
                  badgeText = '🔴 High';
                } else if (active > 2) {
                  bgColor = const Color(0xFFFEFCE8);
                  textColor = const Color(0xFFA16207);
                  badgeText = '🟡 Medium';
                } else {
                  bgColor = const Color(0xFFECFDF5);
                  textColor = const Color(0xFF047857);
                  badgeText = '🟢 Low';
                }

                return Row(
                  children: [
                    CircleAvatar(
                      radius: 16,
                      backgroundColor: const Color(0xFF6366F1).withOpacity(0.1),
                      child: Text(
                        (w['name'] as String).substring(0, 1),
                        style: const TextStyle(
                            color: Color(0xFF6366F1),
                            fontWeight: FontWeight.bold,
                            fontSize: 13),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(w['name'] as String,
                              style: const TextStyle(
                                  fontWeight: FontWeight.bold, fontSize: 14)),
                          Text('$active active ticket(s)',
                              style: TextStyle(
                                  color: Colors.grey.shade600, fontSize: 12)),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                          color: bgColor,
                          borderRadius: BorderRadius.circular(10)),
                      child: Text(badgeText,
                          style: TextStyle(
                              color: textColor,
                              fontSize: 11,
                              fontWeight: FontWeight.bold)),
                    ),
                  ],
                );
              },
            ),
          ],
        ),
      );
    }

    return AppCard(
      padding: const EdgeInsets.all(0),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Padding(
            padding: EdgeInsets.all(24),
            child: Text('💼 Active Technician Workloads',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          ),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: DataTable(
              headingTextStyle: const TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 13,
                  color: Colors.grey),
              dataTextStyle:
                  TextStyle(fontSize: 14, color: theme.colorScheme.onSurface),
              columns: const [
                DataColumn(label: Text('TECHNICIAN NAME')),
                DataColumn(label: Text('ACTIVE TICKETS (WORKLOAD)')),
                DataColumn(label: Text('STATUS INDICATOR')),
              ],
              rows: workloads.map((w) {
                final active = w['active'] as int;
                Color bgColor;
                Color textColor;
                String badgeText;

                if (active > 5) {
                  bgColor = const Color(0xFFFEE2E2);
                  textColor = const Color(0xFFB91C1C);
                  badgeText = '🔴 High Load';
                } else if (active > 2) {
                  bgColor = const Color(0xFFFEFCE8);
                  textColor = const Color(0xFFA16207);
                  badgeText = '🟡 Medium Load';
                } else {
                  bgColor = const Color(0xFFECFDF5);
                  textColor = const Color(0xFF047857);
                  badgeText = '🟢 Low Load';
                }

                return DataRow(cells: [
                  DataCell(Text(w['name'] as String,
                      style: const TextStyle(fontWeight: FontWeight.w600))),
                  DataCell(Text('$active')),
                  DataCell(Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                        color: bgColor,
                        borderRadius: BorderRadius.circular(12)),
                    child: Text(badgeText,
                        style: TextStyle(
                            color: textColor,
                            fontSize: 11,
                            fontWeight: FontWeight.bold)),
                  )),
                ]);
              }).toList(),
            ),
          ),
        ],
      ),
    );
  }
}
