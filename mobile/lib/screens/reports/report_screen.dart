import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_card.dart';
import '../../models/ticket.dart';
import '../../repositories/report_repository.dart';
import '../../services/api_client.dart';
import '../tickets/ticket_detail_screen.dart';

final reportRepositoryProvider =
    Provider((ref) => ReportRepository(ref.read(apiClientProvider)));

final reportStartDateProvider = StateProvider<String>((ref) =>
    DateFormat('yyyy-MM-dd')
        .format(DateTime.now().subtract(const Duration(days: 30))));
final reportEndDateProvider = StateProvider<String>(
    (ref) => DateFormat('yyyy-MM-dd').format(DateTime.now()));
final reportTechnicianProvider = StateProvider<String?>((ref) => null);

final reportDataProvider =
    FutureProvider.autoDispose<Map<String, dynamic>>((ref) async {
  final repo = ref.read(reportRepositoryProvider);
  final startDate = ref.watch(reportStartDateProvider);
  final endDate = ref.watch(reportEndDateProvider);
  final technicianId = ref.watch(reportTechnicianProvider);

  return repo.getReportData(
      startDate: startDate, endDate: endDate, technicianId: technicianId);
});

class ReportScreen extends ConsumerWidget {
  const ReportScreen({super.key});

  Future<void> _selectDate(BuildContext context, WidgetRef ref,
      StateProvider<String> provider) async {
    final currentValue = ref.read(provider);
    DateTime initialDate = DateTime.now();
    try {
      initialDate = DateFormat('yyyy-MM-dd').parse(currentValue);
    } catch (_) {}

    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: DateTime(2000),
      lastDate: DateTime(2100),
    );
    if (picked != null) {
      ref.read(provider.notifier).state =
          DateFormat('yyyy-MM-dd').format(picked);
    }
  }

  void _openTicketDetails(BuildContext context, Map<String, dynamic> t) {
    final ticket = Ticket(
      id: (t['id'] as num).toInt(),
      ticketNumber: t['ticketNumber']?.toString() ?? '',
      title: t['title']?.toString() ?? '',
      description: t['description']?.toString() ?? '',
      category: t['category']?.toString() ?? '',
      priority: t['priority']?.toString() ?? 'Medium',
      status: t['status']?.toString() ?? 'Open',
      createdBy: t['createdBy']?.toString(),
      assignedTo: t['assignedTo']?.toString(),
      createdAt:
          DateTime.tryParse(t['createdAt']?.toString() ?? '') ?? DateTime.now(),
      updatedAt: DateTime.now(),
    );

    Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => TicketDetailScreen(ticket: ticket),
      ),
    );
  }

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isDesktop = MediaQuery.of(context).size.width >= 768;

    final reportAsync = ref.watch(reportDataProvider);
    final startDate = ref.watch(reportStartDateProvider);
    final endDate = ref.watch(reportEndDateProvider);
    final selectedTechnician = ref.watch(reportTechnicianProvider);

    return SingleChildScrollView(
      padding: EdgeInsets.only(
        left: isDesktop ? 24 : 14,
        right: isDesktop ? 24 : 14,
        top: isDesktop ? 24 : 14,
        bottom: 24,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Responsive Mobile / Desktop Header
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Operations Reports',
                style: TextStyle(
                  fontSize: isDesktop ? 30 : 22,
                  fontWeight: FontWeight.w800,
                  color: theme.colorScheme.onSurface,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Export registry data, measure SLA resolution times, and analyze workloads.',
                style: TextStyle(
                  color: theme.colorScheme.onSurface.withOpacity(0.6),
                  fontSize: isDesktop ? 15 : 13,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 20),

          // Filters Card
          AppCard(
            padding: EdgeInsets.all(isDesktop ? 20 : 14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Wrap(
                  spacing: 12,
                  runSpacing: 12,
                  crossAxisAlignment: WrapCrossAlignment.end,
                  children: [
                    _buildDatePicker(
                        'Start Date',
                        startDate,
                        context,
                        isDesktop,
                        () =>
                            _selectDate(context, ref, reportStartDateProvider)),
                    _buildDatePicker('End Date', endDate, context, isDesktop,
                        () => _selectDate(context, ref, reportEndDateProvider)),
                    reportAsync.maybeWhen(
                      data: (data) {
                        final technicians =
                            data['technicians'] as List<dynamic>? ?? [];
                        return _buildDropdown('Technician', selectedTechnician,
                            technicians, context, isDesktop, (val) {
                          ref.read(reportTechnicianProvider.notifier).state =
                              val;
                        });
                      },
                      orElse: () => _buildDropdown(
                          'Technician', null, [], context, isDesktop, (val) {}),
                    ),
                    SizedBox(
                      width: isDesktop ? null : double.infinity,
                      child: Row(
                        mainAxisSize:
                            isDesktop ? MainAxisSize.min : MainAxisSize.max,
                        children: [
                          Expanded(
                            flex: isDesktop ? 0 : 1,
                            child: ElevatedButton.icon(
                              onPressed: () {
                                ref.invalidate(reportDataProvider);
                              },
                              icon: const Icon(Icons.bar_chart, size: 16),
                              label: const Text('Generate'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: theme.colorScheme.surface,
                                foregroundColor: theme.colorScheme.onSurface,
                                side: BorderSide(
                                    color: theme.colorScheme.onSurface
                                        .withOpacity(0.2)),
                                padding: EdgeInsets.symmetric(
                                    horizontal: isDesktop ? 20 : 12,
                                    vertical: 12),
                                shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12)),
                                textStyle: const TextStyle(
                                    fontWeight: FontWeight.bold, fontSize: 13),
                              ),
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            flex: isDesktop ? 0 : 1,
                            child: ElevatedButton.icon(
                              onPressed: () {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                      content:
                                          Text('Exporting CSV report data...'),
                                      backgroundColor: Color(0xFF6366F1)),
                                );
                              },
                              icon: const Icon(Icons.download, size: 16),
                              label: const Text('Export CSV'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF6366F1),
                                foregroundColor: Colors.white,
                                padding: EdgeInsets.symmetric(
                                    horizontal: isDesktop ? 20 : 12,
                                    vertical: 12),
                                shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(12)),
                                textStyle: const TextStyle(
                                    fontWeight: FontWeight.bold, fontSize: 13),
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),

          // Metrics & Registry
          reportAsync.when(
            loading: () => const Padding(
                padding: EdgeInsets.all(40),
                child: Center(child: CircularProgressIndicator())),
            error: (err, stack) => Padding(
                padding: const EdgeInsets.all(40),
                child: Center(child: Text('Error: $err'))),
            data: (data) {
              final totalTickets = data['totalTickets'] ?? 0;
              final resolvedTickets = data['resolvedTickets'] ?? 0;
              final avgResolutionTime = data['avgResolutionTime'] ?? 0.0;
              final formattedAvgTime = avgResolutionTime is num
                  ? avgResolutionTime.toStringAsFixed(1)
                  : '$avgResolutionTime';
              final tickets = data['tickets'] as List<dynamic>? ?? [];

              return Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Metric Cards
                  if (isDesktop)
                    Row(
                      children: [
                        Expanded(
                            child: _buildMetricCard(context, 'Tickets Logged',
                                '$totalTickets', Icons.description_outlined,
                                isDesktop: true)),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                            child: _buildMetricCard(
                                context,
                                'Tickets Resolved',
                                '$resolvedTickets',
                                Icons.check_circle_outline_rounded,
                                isDesktop: true)),
                        const SizedBox(width: AppSpacing.md),
                        Expanded(
                            child: _buildMetricCard(
                                context,
                                'Avg. Resolution Time',
                                '$formattedAvgTime hrs',
                                Icons.timer_outlined,
                                isDesktop: true)),
                      ],
                    )
                  else
                    Row(
                      children: [
                        Expanded(
                            child: _buildMetricCard(context, 'Logged',
                                '$totalTickets', Icons.description_outlined,
                                isDesktop: false)),
                        const SizedBox(width: 8),
                        Expanded(
                            child: _buildMetricCard(
                                context,
                                'Resolved',
                                '$resolvedTickets',
                                Icons.check_circle_outline_rounded,
                                isDesktop: false)),
                        const SizedBox(width: 8),
                        Expanded(
                            child: _buildMetricCard(context, 'Avg Time',
                                '$formattedAvgTime hrs', Icons.timer_outlined,
                                isDesktop: false)),
                      ],
                    ),

                  const SizedBox(height: 20),

                  // Generated Registry Title & List
                  if (!isDesktop) ...[
                    // Mobile Ticket Cards View
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Flexible(
                          child: Text(
                            'Registry Logs',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: theme.colorScheme.onSurface,
                            ),
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: const Color(0xFF6366F1).withOpacity(0.1),
                            borderRadius: BorderRadius.circular(10),
                          ),
                          child: Text(
                            '${tickets.length} matches',
                            style: const TextStyle(
                                color: Color(0xFF6366F1),
                                fontSize: 11,
                                fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),
                    if (tickets.isEmpty)
                      const AppCard(
                        padding: EdgeInsets.all(32),
                        child: Center(
                            child: Text('No ticket logs found.',
                                style:
                                    TextStyle(color: AppColors.textSecondary))),
                      )
                    else
                      ListView.separated(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        itemCount: tickets.length,
                        separatorBuilder: (context, index) =>
                            const SizedBox(height: 10),
                        itemBuilder: (context, index) {
                          final t = tickets[index] as Map<String, dynamic>;
                          return _buildMobileReportTicketCard(
                              context, t, theme);
                        },
                      ),
                  ] else ...[
                    // Desktop Table View
                    AppCard(
                      padding: const EdgeInsets.all(0),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.stretch,
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(20),
                            child: Text(
                                'Generated Registry (${tickets.length} matches)',
                                style: const TextStyle(
                                    fontSize: 17, fontWeight: FontWeight.bold)),
                          ),
                          if (tickets.isEmpty)
                            const Padding(
                              padding: EdgeInsets.symmetric(vertical: 40),
                              child: Center(
                                child: Text(
                                    'No ticket logs found for the selected parameters.',
                                    style: TextStyle(color: Colors.grey)),
                              ),
                            )
                          else
                            SingleChildScrollView(
                              scrollDirection: Axis.horizontal,
                              child: DataTable(
                                headingTextStyle: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 13,
                                    color: Colors.grey),
                                dataTextStyle: TextStyle(
                                    fontSize: 14,
                                    color: theme.colorScheme.onSurface),
                                showCheckboxColumn: false,
                                columns: const [
                                  DataColumn(label: Text('NUMBER')),
                                  DataColumn(label: Text('TITLE')),
                                  DataColumn(label: Text('CATEGORY')),
                                  DataColumn(label: Text('STATUS')),
                                  DataColumn(label: Text('CREATED AT')),
                                  DataColumn(label: Text('ASSIGNED TO')),
                                ],
                                rows: tickets.map((t) {
                                  final tMap = t as Map<String, dynamic>;
                                  return DataRow(
                                    onSelectChanged: (_) =>
                                        _openTicketDetails(context, tMap),
                                    cells: [
                                      DataCell(Text(
                                          tMap['ticketNumber']?.toString() ??
                                              '',
                                          style: const TextStyle(
                                              fontWeight: FontWeight.bold,
                                              color: Color(0xFF6366F1)))),
                                      DataCell(SizedBox(
                                          width: 180,
                                          child: Text(
                                              tMap['title']?.toString() ?? '',
                                              overflow:
                                                  TextOverflow.ellipsis))),
                                      DataCell(Text(
                                          tMap['category']?.toString() ?? '')),
                                      DataCell(_buildStatusBadge(
                                          tMap['status']?.toString() ?? '')),
                                      DataCell(Text(
                                          tMap['createdAt']?.toString() ?? '')),
                                      DataCell(Text(
                                          tMap['assignedTo']?.toString() ??
                                              'Unassigned',
                                          style: const TextStyle(
                                              fontWeight: FontWeight.w500))),
                                    ],
                                  );
                                }).toList(),
                              ),
                            ),
                        ],
                      ),
                    ),
                  ],
                ],
              );
            },
          ),
        ],
      ),
    );
  }

  Widget _buildMobileReportTicketCard(
      BuildContext context, Map<String, dynamic> t, ThemeData theme) {
    final ticketNumber = t['ticketNumber']?.toString() ?? '';
    final title = t['title']?.toString() ?? '';
    final category = t['category']?.toString() ?? '';
    final status = t['status']?.toString() ?? 'Open';
    final priority = t['priority']?.toString() ?? 'Medium';

    return InkWell(
      onTap: () => _openTicketDetails(context, t),
      borderRadius: BorderRadius.circular(14),
      child: AppCard(
        padding: const EdgeInsets.all(14),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Flexible(
                  child: Container(
                    padding:
                        const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFF6366F1).withOpacity(0.1),
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      ticketNumber,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          color: Color(0xFF6366F1),
                          fontWeight: FontWeight.w800,
                          fontSize: 12),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    _buildPriorityBadge(priority),
                    const SizedBox(width: 6),
                    _buildStatusBadge(status),
                  ],
                ),
              ],
            ),
            const SizedBox(height: 10),
            Text(
              title,
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
              style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Icon(Icons.folder_outlined,
                    size: 13, color: Colors.grey.shade500),
                const SizedBox(width: 4),
                Expanded(
                  child: Text(
                    category,
                    overflow: TextOverflow.ellipsis,
                    style:
                        TextStyle(fontSize: 12, color: Colors.grey.shade600),
                  ),
                ),
                const SizedBox(width: 8),
                Flexible(
                  child: Text(
                    t['createdBy']?.toString() ?? '',
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                        fontSize: 11,
                        color: Colors.grey.shade600,
                        fontWeight: FontWeight.w500),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildDatePicker(String label, String value, BuildContext context,
      bool isDesktop, VoidCallback onTap) {
    return SizedBox(
      width: isDesktop ? 150 : double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label.toUpperCase(),
              style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey)),
          const SizedBox(height: 6),
          InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(12),
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
              decoration: BoxDecoration(
                color: Theme.of(context).colorScheme.surface,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.grey.withOpacity(0.25)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(value,
                      style: const TextStyle(
                          fontSize: 13, fontWeight: FontWeight.w500)),
                  const Icon(Icons.calendar_today,
                      size: 16, color: Color(0xFF6366F1)),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildDropdown(
      String label,
      String? selectedValue,
      List<dynamic> technicians,
      BuildContext context,
      bool isDesktop,
      Function(String?) onChanged) {
    List<DropdownMenuItem<String>> items = [
      const DropdownMenuItem(
          value: '',
          child: Text('All Technicians', style: TextStyle(fontSize: 13))),
    ];

    for (var tech in technicians) {
      items.add(DropdownMenuItem(
        value: tech['id'].toString(),
        child: Text(tech['name'].toString(),
            style: const TextStyle(fontSize: 13),
            overflow: TextOverflow.ellipsis),
      ));
    }

    return SizedBox(
      width: isDesktop ? 180 : double.infinity,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(label.toUpperCase(),
              style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey)),
          const SizedBox(height: 6),
          DropdownButtonFormField<String>(
            isExpanded: true,
            value: selectedValue == null || selectedValue.isEmpty
                ? ''
                : selectedValue,
            decoration: InputDecoration(
              filled: true,
              fillColor: Theme.of(context).colorScheme.surface,
              contentPadding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: Colors.grey.withOpacity(0.25))),
              enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: Colors.grey.withOpacity(0.25))),
            ),
            items: items,
            onChanged: onChanged,
          ),
        ],
      ),
    );
  }

  Widget _buildMetricCard(
      BuildContext context, String title, String value, IconData icon,
      {bool isDesktop = true}) {
    return AppCard(
      padding: EdgeInsets.symmetric(
        horizontal: isDesktop ? AppSpacing.md : 10,
        vertical: isDesktop ? AppSpacing.md : 10,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Expanded(
                child: Text(
                  title.toUpperCase(),
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: TextStyle(
                    fontSize: isDesktop ? 11 : 9.5,
                    fontWeight: FontWeight.bold,
                    color: AppColors.textSecondary,
                    letterSpacing: 0.5,
                  ),
                ),
              ),
              Icon(icon, size: isDesktop ? 18 : 14, color: AppColors.primary),
            ],
          ),
          SizedBox(height: isDesktop ? AppSpacing.sm : AppSpacing.xs),
          Text(
            value,
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            style: TextStyle(
              fontSize: isDesktop ? 24 : 16,
              fontWeight: FontWeight.bold,
              color: AppColors.textPrimary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPriorityBadge(String priority) {
    Color color;
    switch (priority.toLowerCase()) {
      case 'low':
        color = const Color(0xFF10B981);
        break;
      case 'medium':
        color = const Color(0xFFF59E0B);
        break;
      case 'high':
        color = const Color(0xFFEF4444);
        break;
      case 'critical':
        color = const Color(0xFF991B1B);
        break;
      default:
        color = Colors.grey;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(priority.toUpperCase(),
          style: TextStyle(
              color: color, fontSize: 9.5, fontWeight: FontWeight.w800)),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color color;
    switch (status.toLowerCase().replaceAll(' ', '')) {
      case 'open':
        color = const Color(0xFF1D4ED8);
        break;
      case 'assigned':
        color = const Color(0xFF7E22CE);
        break;
      case 'inprogress':
        color = const Color(0xFFC2410C);
        break;
      case 'resolved':
        color = const Color(0xFF0F766E);
        break;
      default:
        color = Colors.grey;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
      decoration: BoxDecoration(
        color: color.withOpacity(0.1),
        borderRadius: BorderRadius.circular(6),
      ),
      child: Text(status.toUpperCase(),
          style: TextStyle(
              color: color, fontSize: 9.5, fontWeight: FontWeight.w800)),
    );
  }
}
