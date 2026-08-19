import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/core/widgets/sla_status_badge.dart';
import 'package:mobile/core/widgets/sla_card.dart';
import 'package:mobile/models/ticket_sla.dart';

void main() {
  group('SLA Widgets and Defensive Model Tests', () {
    testWidgets('SlaStatusBadge renders ACTIVE, AT_RISK, BREACHED, PAUSED, COMPLETED correctly', (tester) async {
      await tester.pumpWidget(
        const MaterialApp(
          home: Scaffold(
            body: Column(
              children: [
                SlaStatusBadge(status: 'ACTIVE'),
                SlaStatusBadge(status: 'AT_RISK'),
                SlaStatusBadge(status: 'BREACHED'),
                SlaStatusBadge(status: 'PAUSED'),
                SlaStatusBadge(status: 'COMPLETED'),
              ],
            ),
          ),
        ),
      );

      expect(find.text('ACTIVE'), findsOneWidget);
      expect(find.text('AT RISK'), findsOneWidget);
      expect(find.text('BREACHED'), findsOneWidget);
      expect(find.text('PAUSED'), findsOneWidget);
      expect(find.text('COMPLETED'), findsOneWidget);
    });

    testWidgets('SlaCard renders remaining time and deadlines', (tester) async {
      final sla = TicketSla(
        status: 'AT_RISK',
        firstResponseStatus: 'COMPLETED',
        resolutionStatus: 'AT_RISK',
        resolutionDueAt: DateTime(2026, 8, 18, 16, 30),
        remainingMinutes: 125,
      );

      await tester.pumpWidget(
        MaterialApp(
          home: Scaffold(
            body: SlaCard(sla: sla),
          ),
        ),
      );

      expect(find.text('SLA Tracking'), findsOneWidget);
      expect(find.text('2h 5m remaining'), findsOneWidget);
      expect(find.text('COMPLETED'), findsOneWidget);
    });

    test('TicketSla.fromJson defensive parsing with null json', () {
      final sla = TicketSla.fromJson(null);
      expect(sla.status, equals('ACTIVE'));
      expect(sla.firstResponseStatus, equals('ACTIVE'));
      expect(sla.resolutionStatus, equals('ACTIVE'));
      expect(sla.remainingMinutes, equals(0));
    });
  });
}
