class RealtimeEvent {
  final String eventId;
  final String channel;
  final String type;
  final String timestamp;
  final Map<String, dynamic> payload;

  RealtimeEvent({
    required this.eventId,
    required this.channel,
    required this.type,
    required this.timestamp,
    required this.payload,
  });

  factory RealtimeEvent.fromJson(Map<String, dynamic> json) {
    return RealtimeEvent(
      eventId: json['eventId']?.toString() ?? '',
      channel: json['channel']?.toString() ?? '',
      type: json['type']?.toString() ?? 'unknown',
      timestamp: json['timestamp']?.toString() ?? '',
      payload: (json['payload'] as Map<String, dynamic>?) ?? {},
    );
  }
}
