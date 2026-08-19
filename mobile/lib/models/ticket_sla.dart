class TicketSla {
  final String status;
  final String firstResponseStatus;
  final String resolutionStatus;
  final DateTime? firstResponseDueAt;
  final DateTime? resolutionDueAt;
  final int remainingMinutes;

  TicketSla({
    required this.status,
    required this.firstResponseStatus,
    required this.resolutionStatus,
    this.firstResponseDueAt,
    this.resolutionDueAt,
    this.remainingMinutes = 0,
  });

  factory TicketSla.fromJson(Map<String, dynamic>? json) {
    if (json == null) {
      return TicketSla(
        status: 'ACTIVE',
        firstResponseStatus: 'ACTIVE',
        resolutionStatus: 'ACTIVE',
      );
    }

    return TicketSla(
      status: json['status']?.toString() ?? 'ACTIVE',
      firstResponseStatus: json['firstResponseStatus']?.toString() ?? 'ACTIVE',
      resolutionStatus: json['resolutionStatus']?.toString() ?? 'ACTIVE',
      firstResponseDueAt: json['firstResponseDueAt'] != null
          ? DateTime.tryParse(json['firstResponseDueAt'].toString())
          : null,
      resolutionDueAt: json['resolutionDueAt'] != null
          ? DateTime.tryParse(json['resolutionDueAt'].toString())
          : null,
      remainingMinutes: (json['remainingMinutes'] as num?)?.toInt() ?? 0,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'status': status,
      'firstResponseStatus': firstResponseStatus,
      'resolutionStatus': resolutionStatus,
      'firstResponseDueAt': firstResponseDueAt?.toIso8601String(),
      'resolutionDueAt': resolutionDueAt?.toIso8601String(),
      'remainingMinutes': remainingMinutes,
    };
  }
}
