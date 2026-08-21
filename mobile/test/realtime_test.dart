import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/models/realtime_event.dart';
import 'package:mobile/services/realtime_service.dart';

void main() {
  group('RealtimeService & RealtimeEvent Tests', () {
    test('RealtimeEvent.fromJson defensive parsing', () {
      final json = {
        'eventId': 'evt_123',
        'channel': 'user/5',
        'type': 'notification.created',
        'timestamp': '2026-08-17T15:30:00.000Z',
        'payload': {'title': 'New Reply', 'message': 'Technician responded'}
      };

      final event = RealtimeEvent.fromJson(json);
      expect(event.eventId, equals('evt_123'));
      expect(event.channel, equals('user/5'));
      expect(event.type, equals('notification.created'));
      expect(event.payload['title'], equals('New Reply'));
    });

    test('RealtimeService deduplicates events by eventId', () async {
      final service = RealtimeService();
      int count = 0;

      service.eventStream.listen((event) {
        count++;
      });

      final json = {
        'eventId': 'evt_dup_999',
        'channel': 'user/5',
        'type': 'ticket.updated',
        'timestamp': '2026-08-17T15:30:00.000Z',
        'payload': {'id': 1}
      };

      service.handleIncomingEvent(json);
      service.handleIncomingEvent(json); // Duplicate call

      await pumpEventQueue();
      expect(count, equals(1));
      service.dispose();
    });
  });
}
