class ApprovalRequest {
  final int id;
  final int ticketId;
  final String ticketNumber;
  final String ticketTitle;
  final String requestedBy;
  final String? reason;
  final String status;
  final DateTime requestedAt;

  ApprovalRequest({
    required this.id,
    required this.ticketId,
    required this.ticketNumber,
    required this.ticketTitle,
    required this.requestedBy,
    this.reason,
    required this.status,
    required this.requestedAt,
  });

  factory ApprovalRequest.fromJson(Map<String, dynamic> json) {
    return ApprovalRequest(
      id: (json['id'] as num?)?.toInt() ?? 0,
      ticketId: (json['ticketId'] as num?)?.toInt() ?? 0,
      ticketNumber: json['ticketNumber']?.toString() ?? '',
      ticketTitle: json['ticketTitle']?.toString() ?? '',
      requestedBy: json['requestedBy']?.toString() ?? 'User',
      reason: json['reason']?.toString(),
      status: json['status']?.toString() ?? 'PENDING',
      requestedAt: json['requestedAt'] != null
          ? DateTime.tryParse(json['requestedAt'].toString()) ?? DateTime.now()
          : DateTime.now(),
    );
  }
}
