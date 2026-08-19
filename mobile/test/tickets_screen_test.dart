import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile/models/ticket.dart';
import 'package:mobile/screens/tickets/ticket_list_screen.dart';

void main() {
  group('TicketListScreen Four-State Integrity Tests', () {
    testWidgets('State 1: Loading state renders safely without RenderFlex or viewport crashes',
        (tester) async {
      final completer = Completer<List<Ticket>>();

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            ticketsProvider.overrideWith((ref) => completer.future),
          ],
          child: const MaterialApp(
            home: Scaffold(body: TicketListScreen()),
          ),
        ),
      );

      // Verify header and filter UI render while loading
      expect(find.text('Service Desk Tickets'), findsOneWidget);
      expect(tester.takeException(), isNull);

      completer.complete([]);
      await tester.pumpAndSettle();
    });

    testWidgets('State 2: Success with tickets renders native ticket cards',
        (tester) async {
      final sampleTickets = [
        Ticket(
          id: 1,
          ticketNumber: 'TCK-2026-0001',
          title: 'Database connection timeout',
          description: 'Cannot connect to MariaDB cluster.',
          category: 'IT Support',
          priority: 'High',
          status: 'Open',
          createdAt: DateTime(2026, 8, 18, 10, 0),
          updatedAt: DateTime(2026, 8, 18, 10, 0),
        ),
      ];

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            ticketsProvider.overrideWith((ref) async => sampleTickets),
          ],
          child: const MaterialApp(
            home: Scaffold(body: TicketListScreen()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Service Desk Tickets'), findsOneWidget);
      expect(find.text('TCK-2026-0001'), findsOneWidget);
      expect(find.text('Database connection timeout'), findsOneWidget);
      expect(find.text('IT Support'), findsWidgets);
    });

    testWidgets('State 3: Success with zero tickets renders EmptyState',
        (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            ticketsProvider.overrideWith((ref) async => <Ticket>[]),
          ],
          child: const MaterialApp(
            home: Scaffold(body: TicketListScreen()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Service Desk Tickets'), findsOneWidget);
      expect(find.text('No tickets yet'), findsOneWidget);
      expect(find.text('Create Ticket'), findsWidgets);
    });

    testWidgets('State 4: API Error renders ErrorState with retry button and error details',
        (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            ticketsProvider.overrideWith((ref) async => throw Exception('Network timeout 504')),
          ],
          child: const MaterialApp(
            home: Scaffold(body: TicketListScreen()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Service Desk Tickets'), findsOneWidget);
      expect(find.text('Tickets failed to load'), findsOneWidget);
      expect(find.text('Try Again'), findsOneWidget);
      expect(tester.takeException(), isNull);
    });
  });
}
