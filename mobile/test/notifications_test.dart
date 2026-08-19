import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/models/user_notification.dart';

void main() {
  group('UserNotification Defensive Parsing Tests', () {
    test('UserNotification.fromJson defensive parsing', () {
      final json = {
        'id': 42,
        'type': 'ticket_assigned',
        'title': 'Ticket Assigned',
        'message': 'You have been assigned to Ticket #100',
        'entityType': 'ticket',
        'entityId': 100,
        'isRead': false,
        'createdAt': '2026-08-17 15:30:00',
      };

      final notification = UserNotification.fromJson(json);
      expect(notification.id, equals(42));
      expect(notification.type, equals('ticket_assigned'));
      expect(notification.title, equals('Ticket Assigned'));
      expect(notification.entityId, equals(100));
      expect(notification.isRead, isFalse);
    });
  });
}
