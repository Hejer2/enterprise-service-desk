class ExecutiveAnalytics {
  final int totalTickets;
  final int openTickets;
  final int resolvedTickets;
  final int closedTickets;
  final double slaCompliancePct;
  final int slaBreaches;
  final double avgCsat;
  final int csatRatingsCount;
  final int automationExecutions;
  final List<TechnicianPerformance> technicians;

  ExecutiveAnalytics({
    required this.totalTickets,
    required this.openTickets,
    required this.resolvedTickets,
    required this.closedTickets,
    required this.slaCompliancePct,
    required this.slaBreaches,
    required this.avgCsat,
    required this.csatRatingsCount,
    required this.automationExecutions,
    required this.technicians,
  });

  factory ExecutiveAnalytics.fromJson(Map<String, dynamic> json) {
    final kpis = (json['kpis'] as Map<String, dynamic>?) ?? {};
    final techs = (json['technicians'] as List<dynamic>?) ?? [];

    return ExecutiveAnalytics(
      totalTickets: (kpis['totalTickets'] as num?)?.toInt() ?? 0,
      openTickets: (kpis['openTickets'] as num?)?.toInt() ?? 0,
      resolvedTickets: (kpis['resolvedTickets'] as num?)?.toInt() ?? 0,
      closedTickets: (kpis['closedTickets'] as num?)?.toInt() ?? 0,
      slaCompliancePct: (kpis['slaCompliancePct'] as num?)?.toDouble() ?? 100.0,
      slaBreaches: (kpis['slaBreaches'] as num?)?.toInt() ?? 0,
      avgCsat: (kpis['avgCsat'] as num?)?.toDouble() ?? 0.0,
      csatRatingsCount: (kpis['csatRatingsCount'] as num?)?.toInt() ?? 0,
      automationExecutions: (kpis['automationExecutions'] as num?)?.toInt() ?? 0,
      technicians: techs.map((t) => TechnicianPerformance.fromJson(t)).toList(),
    );
  }
}

class TechnicianPerformance {
  final int id;
  final String name;
  final int assigned;
  final int resolved;
  final double completionRate;

  TechnicianPerformance({
    required this.id,
    required this.name,
    required this.assigned,
    required this.resolved,
    required this.completionRate,
  });

  factory TechnicianPerformance.fromJson(Map<String, dynamic> json) {
    return TechnicianPerformance(
      id: (json['id'] as num?)?.toInt() ?? 0,
      name: json['name']?.toString() ?? 'Technician',
      assigned: (json['assigned'] as num?)?.toInt() ?? 0,
      resolved: (json['resolved'] as num?)?.toInt() ?? 0,
      completionRate: (json['completionRate'] as num?)?.toDouble() ?? 0.0,
    );
  }
}
