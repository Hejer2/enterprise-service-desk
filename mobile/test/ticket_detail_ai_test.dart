import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:mobile/models/ai_reply.dart';
import 'package:mobile/models/ai_summary.dart';
import 'package:mobile/models/ai_ticket_analysis.dart';
import 'package:mobile/models/ticket.dart';
import 'package:mobile/models/user.dart';
import 'package:mobile/models/role.dart';
import 'package:mobile/repositories/ai_repository.dart';
import 'package:mobile/screens/auth/login_screen.dart';
import 'package:mobile/screens/tickets/ticket_detail_screen.dart';
import 'package:mobile/screens/tickets/widgets/ai_assistant_card.dart';

class FakeAiRepository implements AiRepository {
  @override
  Future<AiTicketAnalysis> classifyTicket(int ticketId) async {
    return AiTicketAnalysis(
      category: 'Network & Connectivity',
      priority: 'High',
      suggestedTeam: 'Network Operations',
      confidence: 0.94,
      reason: 'Issue relates to connection timeout and gateway latency.',
    );
  }

  @override
  Future<AiSummary> summarizeTicket(int ticketId) async {
    return AiSummary(
      problem: 'Cannot connect to database cluster from mobile clients.',
      details: ['Error 504 Gateway Timeout', 'Happens on Wi-Fi and 5G'],
      actionsTaken: ['Restarted backend service', 'Verified proxy routing'],
      currentStatus: 'In Progress',
      nextStep: 'Inspect MariaDB pool configuration',
    );
  }

  @override
  Future<AiReply> generateReply(int ticketId,
      {String action = 'generate', String? context}) async {
    return AiReply(
      draft:
          'Hello! We have identified the connection timeout and our team is actively restarting the cluster pool.',
      action: action,
      isDraftOnly: true,
    );
  }

  @override
  Future<List<Map<String, dynamic>>> findSimilarTickets(int ticketId) async {
    return [
      {
        'ticketNumber': 'TCK-2026-0042',
        'title': 'Database connection pool exhausted',
        'similarityScore': 0.88,
        'resolution': 'Increased max connections from 100 to 500.',
      }
    ];
  }

  @override
  Future<Map<String, dynamic>> recommendResolution(int ticketId) async {
    return {
      'recommendedAction': 'Restart connection pool and flush query cache',
      'suggestedSteps': [
        'Check database logs in /var/log/mariadb',
        'Run pool flush command',
        'Notify employee when healthy'
      ],
    };
  }

  @override
  Future<Map<String, dynamic>> askKnowledge(int ticketId, {String? query}) async {
    return {
      'answer': 'Verify MariaDB max_connections parameter.',
      'articles': [
        {'title': 'Resolving Database Connection Timeouts', 'id': 101}
      ],
    };
  }

  @override
  Future<Map<String, dynamic>> generateExecutiveInsights() async {
    return {
      'summary': 'Overall system ticket resolution rate improved by 14%.',
      'insights': ['Network category has highest response time.'],
    };
  }
}

void main() {
  final sampleTicket = Ticket(
    id: 1,
    ticketNumber: 'TCK-2026-0001',
    title: 'Database connection timeout',
    description: 'Cannot connect to MariaDB cluster.',
    category: 'IT Support',
    priority: 'High',
    status: 'In Progress',
    createdAt: DateTime(2026, 8, 18, 10, 0),
    updatedAt: DateTime(2026, 8, 18, 10, 0),
  );

  group('Ticket Details Feature Parity & AI Assistant Tests', () {
    testWidgets('Technician role sees AI Assistant Card and all 6 AI actions',
        (tester) async {
      tester.view.physicalSize = const Size(400, 1000);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      final techUser = User(
        id: 1,
        email: 'tech@example.com',
        firstName: 'Alex',
        lastName: 'Tech',
        roleEntity: Role(id: 2, name: 'ROLE_IT_TECH', displayName: 'IT Technician'),
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => techUser),
            aiRepositoryProvider.overrideWith((ref) => FakeAiRepository()),
            ticketDetailsProvider(1).overrideWith((ref) async => {
                  'id': 1,
                  'ticketNumber': 'TCK-2026-0001',
                  'title': 'Database connection timeout',
                  'description': 'Cannot connect to MariaDB cluster.',
                  'status': 'In Progress',
                  'priority': 'High',
                  'category': 'IT Support',
                  'attachments': [],
                }),
            ticketMessagesProvider(1).overrideWith((ref) async => []),
            ticketTechniciansProvider(1).overrideWith((ref) async => []),
            ticketActivitiesProvider(1).overrideWith((ref) async => {'items': []}),
          ],
          child: MaterialApp(
            home: TicketDetailScreen(ticket: sampleTicket),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Check AI Assistant section is visible for Technician
      expect(find.text('AI Assistant'), findsOneWidget);
      expect(find.text('✨ Analyze Ticket'), findsWidgets);
      expect(find.text('📝 Summarize'), findsWidgets);
      expect(find.text('💬 Draft Reply'), findsWidgets);
      expect(find.text('🔎 Similar Tickets'), findsWidgets);
      expect(find.text('📚 Knowledge Base'), findsWidgets);
      expect(find.text('🛠 Resolution'), findsWidgets);

      // Check AI Reply Assistant icon is present in composer
      expect(find.byIcon(Icons.auto_awesome), findsWidgets);
    });

    testWidgets('Tapping 💬 Draft Reply inserts draft into composer without sending',
        (tester) async {
      tester.view.physicalSize = const Size(400, 1000);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      final techUser = User(
        id: 1,
        email: 'tech@example.com',
        firstName: 'Alex',
        lastName: 'Tech',
        roleEntity: Role(id: 2, name: 'ROLE_IT_TECH', displayName: 'IT Technician'),
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => techUser),
            aiRepositoryProvider.overrideWith((ref) => FakeAiRepository()),
            ticketDetailsProvider(1).overrideWith((ref) async => {
                  'id': 1,
                  'ticketNumber': 'TCK-2026-0001',
                  'title': 'Database connection timeout',
                  'description': 'Cannot connect to MariaDB cluster.',
                  'status': 'In Progress',
                  'priority': 'High',
                  'category': 'IT Support',
                  'attachments': [],
                }),
            ticketMessagesProvider(1).overrideWith((ref) async => []),
            ticketTechniciansProvider(1).overrideWith((ref) async => []),
            ticketActivitiesProvider(1).overrideWith((ref) async => {'items': []}),
          ],
          child: MaterialApp(
            home: TicketDetailScreen(ticket: sampleTicket),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Tap on Draft Reply button
      await tester.tap(find.text('💬 Draft Reply'));
      await tester.pumpAndSettle();

      // Verify composer text is populated with the AI draft
      expect(
        find.textContaining('Hello! We have identified the connection timeout'),
        findsOneWidget,
      );
    });

    testWidgets('Employee role does NOT see technician AI card but sees conversation and details',
        (tester) async {
      tester.view.physicalSize = const Size(400, 1000);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      final empUser = User(
        id: 2,
        email: 'employee@example.com',
        firstName: 'John',
        lastName: 'Employee',
        roleEntity: Role(id: 1, name: 'ROLE_EMPLOYEE', displayName: 'Employee'),
      );

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => empUser),
            aiRepositoryProvider.overrideWith((ref) => FakeAiRepository()),
            ticketDetailsProvider(1).overrideWith((ref) async => {
                  'id': 1,
                  'ticketNumber': 'TCK-2026-0001',
                  'title': 'Database connection timeout',
                  'description': 'Cannot connect to MariaDB cluster.',
                  'status': 'In Progress',
                  'priority': 'High',
                  'category': 'IT Support',
                  'attachments': [],
                }),
            ticketMessagesProvider(1).overrideWith((ref) async => []),
            ticketTechniciansProvider(1).overrideWith((ref) async => []),
            ticketActivitiesProvider(1).overrideWith((ref) async => {'items': []}),
          ],
          child: MaterialApp(
            home: TicketDetailScreen(ticket: sampleTicket),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Employee should NOT see AI Assistant
      expect(find.text('AI Assistant'), findsNothing);

      // Employee sees Ticket Conversation
      expect(find.text('TICKET CONVERSATION'), findsOneWidget);
      expect(find.text('Send'), findsOneWidget);
    });
  });
}
