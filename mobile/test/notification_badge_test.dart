import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:mobile/core/widgets/main_scaffold.dart';
import 'package:mobile/providers/notification_providers.dart';
import 'package:mobile/screens/auth/login_screen.dart';

class MockUnreadNotifier extends StateNotifier<int>
    implements UnreadNotificationsNotifier {
  MockUnreadNotifier(super.initial);

  @override
  void clear() => state = 0;

  @override
  void decrement() {
    if (state > 0) state = state - 1;
  }

  @override
  Future<void> refresh() async {}

  @override
  void setCount(int count) => state = count >= 0 ? count : 0;
}

void main() {
  setUpAll(() {
    GoogleFonts.config.allowRuntimeFetching = false;
  });

  group('Notification Unread Badge in Top AppBar Tests', () {
    testWidgets('TEST 1: Bottom navigation has NO notification item, Top AppBar has Notification icon without badge when unread = 0',
        (tester) async {
      tester.view.physicalSize = const Size(400, 800);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authProvider.overrideWith((ref) => true),
            unreadNotificationsCountProvider.overrideWith((ref) {
              return MockUnreadNotifier(0);
            }),
          ],
          child: const MaterialApp(
            home: MainScaffold(child: Text('Dashboard Content')),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // 1. Verify Bottom Navigation has NO Notifications tab/item
      expect(
        find.descendant(
          of: find.byType(NavigationBar),
          matching: find.text('Notifications'),
        ),
        findsNothing,
      );

      // 2. Verify Top AppBar has the Notification bell icon
      expect(
        find.descendant(
          of: find.byType(AppBar),
          matching: find.byTooltip('Notifications'),
        ),
        findsOneWidget,
      );

      // 3. Verify no Badge is shown when unread count = 0
      expect(find.byType(Badge), findsNothing);
    });

    testWidgets('TEST 2: Top AppBar badge shows "1" when unread count = 1',
        (tester) async {
      tester.view.physicalSize = const Size(400, 800);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authProvider.overrideWith((ref) => true),
            unreadNotificationsCountProvider.overrideWith((ref) {
              return MockUnreadNotifier(1);
            }),
          ],
          child: const MaterialApp(
            home: MainScaffold(child: Text('Dashboard Content')),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.byType(Badge), findsOneWidget);
      expect(find.text('1'), findsOneWidget);
    });

    testWidgets('TEST 3: Top AppBar badge shows "5" when unread count = 5',
        (tester) async {
      tester.view.physicalSize = const Size(400, 800);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authProvider.overrideWith((ref) => true),
            unreadNotificationsCountProvider.overrideWith((ref) {
              return MockUnreadNotifier(5);
            }),
          ],
          child: const MaterialApp(
            home: MainScaffold(child: Text('Dashboard Content')),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.byType(Badge), findsOneWidget);
      expect(find.text('5'), findsOneWidget);
    });

    testWidgets('TEST 4: Top AppBar badge shows "12" when unread count = 12',
        (tester) async {
      tester.view.physicalSize = const Size(400, 800);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authProvider.overrideWith((ref) => true),
            unreadNotificationsCountProvider.overrideWith((ref) {
              return MockUnreadNotifier(12);
            }),
          ],
          child: const MaterialApp(
            home: MainScaffold(child: Text('Dashboard Content')),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.byType(Badge), findsOneWidget);
      expect(find.text('12'), findsOneWidget);
    });

    testWidgets('TEST 5: Top AppBar badge shows "99+" when unread count > 99',
        (tester) async {
      tester.view.physicalSize = const Size(400, 800);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authProvider.overrideWith((ref) => true),
            unreadNotificationsCountProvider.overrideWith((ref) {
              return MockUnreadNotifier(120);
            }),
          ],
          child: const MaterialApp(
            home: MainScaffold(child: Text('Dashboard Content')),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.byType(Badge), findsOneWidget);
      expect(find.text('99+'), findsOneWidget);
    });

    testWidgets('TEST 6: State decrement on mark as read and clear on mark all read',
        (tester) async {
      final notifier = MockUnreadNotifier(3);
      expect(notifier.state, 3);

      // Decrement on single read
      notifier.decrement();
      expect(notifier.state, 2);

      notifier.decrement();
      expect(notifier.state, 1);

      // Decrement to 0
      notifier.decrement();
      expect(notifier.state, 0);

      // Cannot go below 0
      notifier.decrement();
      expect(notifier.state, 0);

      // Clear all
      notifier.setCount(15);
      expect(notifier.state, 15);
      notifier.clear();
      expect(notifier.state, 0);
    });
  });
}
