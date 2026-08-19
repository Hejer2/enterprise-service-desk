import 'ticket_sla.dart';

class Ticket {
  final int id;
  final String ticketNumber;
  final String title;
  final String description;
  final String category;
  final String priority;
  final String status;
  final String? createdBy;
  final String? assignedTo;
  final DateTime? dueDate;
  final DateTime createdAt;
  final DateTime updatedAt;
  final DateTime? closedAt;
  final TicketSla? sla;

  Ticket({
    required this.id,
    required this.ticketNumber,
    required this.title,
    required this.description,
    required this.category,
    required this.priority,
    required this.status,
    this.createdBy,
    this.assignedTo,
    this.dueDate,
    required this.createdAt,
    required this.updatedAt,
    this.closedAt,
    this.sla,
  });

  factory Ticket.fromJson(Map<String, dynamic> json) {
    String parseUser(dynamic val) {
      if (val == null) return '';
      if (val is String) return val;
      if (val is Map) return val['fullName']?.toString() ?? val['email']?.toString() ?? '';
      return val.toString();
    }

    return Ticket(
      id: (json['id'] as num?)?.toInt() ?? (json['ticketId'] as num?)?.toInt() ?? 0,
      ticketNumber: json['ticketNumber']?.toString() ?? json['ticket_number']?.toString() ?? '',
      title: json['title']?.toString() ?? '',
      description: json['description']?.toString() ?? '',
      category: json['category']?.toString() ?? '',
      priority: json['priority']?.toString() ?? 'Medium',
      status: json['status']?.toString() ?? 'Open',
      createdBy: parseUser(json['createdBy'] ?? json['created_by']),
      assignedTo: parseUser(json['assignedTo'] ?? json['assigned_to']),
      dueDate: json['dueDate'] != null
          ? DateTime.tryParse(json['dueDate'].toString())
          : (json['due_date'] != null ? DateTime.tryParse(json['due_date'].toString()) : null),
      createdAt: json['createdAt'] != null
          ? (DateTime.tryParse(json['createdAt'].toString()) ?? DateTime.now())
          : (json['created_at'] != null ? (DateTime.tryParse(json['created_at'].toString()) ?? DateTime.now()) : DateTime.now()),
      updatedAt: json['updatedAt'] != null
          ? (DateTime.tryParse(json['updatedAt'].toString()) ?? DateTime.now())
          : (json['updated_at'] != null ? (DateTime.tryParse(json['updated_at'].toString()) ?? DateTime.now()) : DateTime.now()),
      closedAt: json['closedAt'] != null
          ? DateTime.tryParse(json['closedAt'].toString())
          : (json['closed_at'] != null ? DateTime.tryParse(json['closed_at'].toString()) : null),
      sla: json['sla'] is Map ? TicketSla.fromJson(Map<String, dynamic>.from(json['sla'] as Map)) : null,
    );
  }
}
