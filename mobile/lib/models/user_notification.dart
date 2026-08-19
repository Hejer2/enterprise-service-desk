class UserNotification {
  final int id;
  final String type;
  final String title;
  final String message;
  final String? entityType;
  final int? entityId;
  final bool isRead;
  final DateTime createdAt;

  UserNotification({
    required this.id,
    required this.type,
    required this.title,
    required this.message,
    this.entityType,
    this.entityId,
    this.isRead = false,
    required this.createdAt,
  });

  factory UserNotification.fromJson(Map<String, dynamic> json) {
    return UserNotification(
      id: (json['id'] as num?)?.toInt() ?? 0,
      type: json['type']?.toString() ?? 'system',
      title: json['title']?.toString() ?? '',
      message: json['message']?.toString() ?? json['content']?.toString() ?? '',
      entityType: json['entityType']?.toString() ?? json['entity_type']?.toString(),
      entityId: (json['entityId'] as num?)?.toInt() ?? (json['entity_id'] as num?)?.toInt(),
      isRead: json['isRead'] as bool? ?? json['is_read'] as bool? ?? false,
      createdAt: json['createdAt'] != null
          ? (DateTime.tryParse(json['createdAt'].toString()) ?? DateTime.now())
          : (json['created_at'] != null ? (DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()) : DateTime.now()),
    );
  }
}
