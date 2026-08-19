class AiSummary {
  final String problem;
  final List<String> details;
  final List<String> actionsTaken;
  final String currentStatus;
  final String nextStep;

  AiSummary({
    required this.problem,
    required this.details,
    required this.actionsTaken,
    required this.currentStatus,
    required this.nextStep,
  });

  factory AiSummary.fromJson(Map<String, dynamic> json) {
    return AiSummary(
      problem: json['problem']?.toString() ?? '',
      details: (json['details'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? [],
      actionsTaken: (json['actionsTaken'] as List<dynamic>?)?.map((e) => e.toString()).toList() ?? [],
      currentStatus: json['currentStatus']?.toString() ?? 'Open',
      nextStep: json['nextStep']?.toString() ?? '',
    );
  }
}
