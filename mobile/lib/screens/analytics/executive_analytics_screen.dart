import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../core/theme/app_theme.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/empty_state.dart';
import '../../core/widgets/error_state.dart';
import '../../core/widgets/skeleton_loader.dart';
import '../../models/executive_analytics.dart';
import '../../repositories/analytics_repository.dart';
import '../../services/api_client.dart';

final analyticsRepositoryProvider =
    Provider((ref) => AnalyticsRepository(ref.read(apiClientProvider)));

final executiveAnalyticsProvider =
    FutureProvider.autoDispose<ExecutiveAnalytics>((ref) async {
  final repo = ref.read(analyticsRepositoryProvider);
  return repo.getExecutiveAnalytics();
});

class ExecutiveAnalyticsScreen extends ConsumerWidget {
  const ExecutiveAnalyticsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final analyticsAsync = ref.watch(executiveAnalyticsProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Executive Analytics'),
      ),
      body: analyticsAsync.when(
        loading: () => const Padding(
          padding: EdgeInsets.all(16),
          child: SkeletonLoader(width: double.infinity, height: 140),
        ),
        error: (err, stack) => ErrorState(
          title: 'Analytics Access Restricted or Failed',
          message: err.toString(),
          onRetry: () => ref.invalidate(executiveAnalyticsProvider),
        ),
        data: (data) {
          return RefreshIndicator(
            onRefresh: () async => ref.invalidate(executiveAnalyticsProvider),
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // KPI Grid
                  GridView.count(
                    crossAxisCount: 2,
                    shrinkWrap: true,
                    physics: const NeverScrollableScrollPhysics(),
                    crossAxisSpacing: 12,
                    mainAxisSpacing: 12,
                    childAspectRatio: 1.3,
                    children: [
                      _buildKpiCard('Total Tickets', '${data.totalTickets}', Icons.confirmation_number_outlined, AppColors.primary),
                      _buildKpiCard('Open Tickets', '${data.openTickets}', Icons.pending_actions_rounded, Colors.orange),
                      _buildKpiCard('SLA Compliance', '${data.slaCompliancePct}%', Icons.verified_user_outlined, Colors.green),
                      _buildKpiCard('Avg CSAT', '⭐ ${data.avgCsat}', Icons.star_rate_rounded, Colors.amber),
                    ],
                  ),
                  const SizedBox(height: 24),

                  // Automation Card
                  AppCard(
                    padding: const EdgeInsets.all(16),
                    child: Row(
                      children: [
                        const CircleAvatar(
                          backgroundColor: Color(0xFFF3E8FF),
                          child: Icon(Icons.bolt_rounded, color: Color(0xFF8B5CF6)),
                        ),
                        const SizedBox(width: 14),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              const Text('Automation Executions', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                              Text('${data.automationExecutions} automated actions triggered', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 24),

                  // Technician Leaderboard Section
                  const Text('Technician Performance', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 12),
                  if (data.technicians.isEmpty)
                    const EmptyState(
                      icon: Icons.person_off_outlined,
                      title: 'No Technician Data',
                      message: 'No technician assignments recorded for this period.',
                    )
                  else
                    ListView.separated(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: data.technicians.length,
                      separatorBuilder: (context, index) => const SizedBox(height: 8),
                      itemBuilder: (context, index) {
                        final tech = data.technicians[index];
                        return AppCard(
                          padding: const EdgeInsets.all(12),
                          child: Row(
                            children: [
                              CircleAvatar(
                                radius: 18,
                                backgroundColor: AppColors.primaryLight,
                                child: Text(tech.name.substring(0, 1), style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.primary)),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(tech.name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                                    Text('${tech.resolved} / ${tech.assigned} resolved (${tech.completionRate}%)', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        );
                      },
                    ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildKpiCard(String title, String value, IconData icon, Color color) {
    return AppCard(
      padding: const EdgeInsets.all(14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(title, style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Colors.grey)),
              Icon(icon, size: 18, color: color),
            ],
          ),
          const SizedBox(height: 8),
          Text(value, style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: color)),
        ],
      ),
    );
  }
}
