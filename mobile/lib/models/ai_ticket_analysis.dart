class AiTicketAnalysis {
  final String category;
  final String priority;
  final String suggestedTeam;
  final double confidence;
  final String reason;

  AiTicketAnalysis({
    required this.category,
    required this.priority,
    required this.suggestedTeam,
    required this.confidence,
    required this.reason,
  });

  factory AiTicketAnalysis.fromJson(Map<String, dynamic> json) {
    return AiTicketAnalysis(
      category: json['category']?.toString() ?? 'IT Support',
      priority: json['priority']?.toString() ?? 'Medium',
      suggestedTeam: json['suggestedTeam']?.toString() ?? 'IT Support',
      confidence: (json['confidence'] as num?)?.toDouble() ?? 0.85,
      reason: json['reason']?.toString() ?? 'AI Content Analysis',
    );
  }
}
