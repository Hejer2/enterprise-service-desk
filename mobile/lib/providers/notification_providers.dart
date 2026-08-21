import 'dart:async';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../models/realtime_event.dart';
import '../models/user_notification.dart';
import '../repositories/notification_repository.dart';
import '../services/api_client.dart';
import '../services/realtime_service.dart';

final realtimeServiceProvider = Provider<RealtimeService>((ref) {
  final service = RealtimeService();
  ref.onDispose(() => service.dispose());
  return service;
});

final notificationRepositoryProvider = Provider<NotificationRepository>((ref) {
  return NotificationRepository(ref.read(apiClientProvider));
});

final notificationsProvider = FutureProvider.autoDispose<List<UserNotification>>((ref) async {
  final repo = ref.read(notificationRepositoryProvider);
  return repo.getNotifications();
});

class UnreadNotificationsNotifier extends StateNotifier<int> {
  final NotificationRepository _repo;
  final RealtimeService _realtimeService;
  StreamSubscription<RealtimeEvent>? _subscription;

  UnreadNotificationsNotifier(this._repo, this._realtimeService, {bool autoFetch = true}) : super(0) {
    if (autoFetch) {
      _fetchInitial();
      _listenToRealtimeEvents();
    }
  }

  Future<void> _fetchInitial() async {
    try {
      final count = await _repo.getUnreadCount();
      state = count;
    } catch (_) {
      // Keep previous or default to 0 gracefully
    }
  }

  void _listenToRealtimeEvents() {
    _subscription = _realtimeService.eventStream.listen((event) {
      final type = event.type.toLowerCase();
      if (type.contains('notification') ||
          type.contains('ticket') ||
          type.contains('sla') ||
          type.contains('approval') ||
          type.contains('status')) {
        refresh();
      }
    });
  }

  Future<void> refresh() async {
    try {
      final count = await _repo.getUnreadCount();
      state = count;
    } catch (_) {}
  }

  void decrement() {
    if (state > 0) {
      state = state - 1;
    }
  }

  void clear() {
    state = 0;
  }

  void setCount(int count) {
    state = count >= 0 ? count : 0;
  }

  @override
  void dispose() {
    _subscription?.cancel();
    super.dispose();
  }
}

final unreadNotificationsCountProvider =
    StateNotifierProvider<UnreadNotificationsNotifier, int>((ref) {
  final repo = ref.watch(notificationRepositoryProvider);
  final realtime = ref.watch(realtimeServiceProvider);
  return UnreadNotificationsNotifier(repo, realtime);
});
