import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/screens/reports/report_screen.dart';

void main() {
  group('ReportScreen Overflow and Responsive Layout Tests', () {
    testWidgets('Renders on narrow mobile screen (320px width) without RenderFlex overflow',
        (tester) async {
      tester.view.physicalSize = const Size(320, 700);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      final fakeData = {
        'totalTickets': 42,
        'resolvedTickets': 38,
        'avgResolutionTime': 2.333333,
        'technicians': [
          {'id': '1', 'name': 'Technician Alpha'},
          {'id': '2', 'name': 'Support Beta'},
        ],
        'tickets': [
          {
            'id': 101,
            'ticketNumber': 'TICK-2026-LONG-NUM',
            'title': 'Network database connection timeout and latency issues on main cluster',
            'category': 'Database & Infrastructure Maintenance',
            'priority': 'Critical',
            'status': 'In Progress',
            'createdBy': 'employee.verylongemailaddress@enterprise.com',
            'assignedTo': 'Tech Alpha',
            'createdAt': '2026-08-18 10:00:00',
          },
        ],
      };

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            reportDataProvider.overrideWith((ref) => Future.value(fakeData)),
          ],
          child: const MaterialApp(
            home: Scaffold(
              body: ReportScreen(),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Operations Reports'), findsOneWidget);
      expect(find.text('Generate'), findsOneWidget);
      expect(find.text('Export CSV'), findsOneWidget);
      expect(find.text('LOGGED'), findsOneWidget);
      expect(find.text('RESOLVED'), findsOneWidget);
      expect(find.text('AVG TIME'), findsOneWidget);
      expect(find.text('Registry Logs'), findsOneWidget);

      // Verify no RenderFlex overflow exceptions occurred
      expect(tester.takeException(), isNull);
    });

    testWidgets('Renders on standard mobile screen (375px width) cleanly without overflow',
        (tester) async {
      tester.view.physicalSize = const Size(375, 812);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      final fakeData = {
        'totalTickets': 15,
        'resolvedTickets': 12,
        'avgResolutionTime': 4.5,
        'technicians': [],
        'tickets': [],
      };

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            reportDataProvider.overrideWith((ref) => Future.value(fakeData)),
          ],
          child: const MaterialApp(
            home: Scaffold(
              body: ReportScreen(),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Operations Reports'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  });
}
