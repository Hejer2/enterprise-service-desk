class AiReply {
  final String draft;
  final String action;
  final bool isDraftOnly;

  AiReply({
    required this.draft,
    required this.action,
    this.isDraftOnly = true,
  });

  factory AiReply.fromJson(Map<String, dynamic> json) {
    return AiReply(
      draft: json['draft']?.toString() ?? '',
      action: json['action']?.toString() ?? 'generate',
      isDraftOnly: json['isDraftOnly'] as bool? ?? true,
    );
  }
}
