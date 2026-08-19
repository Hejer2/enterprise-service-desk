import 'dart:async';
import 'dart:developer';
import '../models/realtime_event.dart';

class RealtimeService {
  final _eventController = StreamController<RealtimeEvent>.broadcast();
  final Set<String> _processedEventIds = {};
  bool _isConnected = false;

  Stream<RealtimeEvent> get eventStream => _eventController.stream;
  bool get isConnected => _isConnected;

  void handleIncomingEvent(Map<String, dynamic> json) {
    try {
      final event = RealtimeEvent.fromJson(json);
      if (event.eventId.isNotEmpty && _processedEventIds.contains(event.eventId)) {
        return;
      }
      if (event.eventId.isNotEmpty) {
        _processedEventIds.add(event.eventId);
        if (_processedEventIds.length > 200) {
          _processedEventIds.remove(_processedEventIds.first);
        }
      }
      _isConnected = true;
      _eventController.add(event);
    } catch (e) {
      log('Error parsing RealtimeEvent: $e');
    }
  }

  void markDisconnected() {
    _isConnected = false;
  }

  void dispose() {
    _eventController.close();
  }
}
