import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/models/ai_reply.dart';
import 'package:mobile/models/ai_summary.dart';
import 'package:mobile/models/ai_ticket_analysis.dart';

void main() {
  group('Phase 4 AI Defensive Model Parsing Tests', () {
    test('AiTicketAnalysis.fromJson defensive parsing', () {
      final json = {
        'category': 'IT Support',
        'priority': 'High',
        'suggestedTeam': 'Network Team',
        'confidence': 0.94,
        'reason': 'Network errors detected in ticket description.',
      };

      final analysis = AiTicketAnalysis.fromJson(json);
      expect(analysis.category, equals('IT Support'));
      expect(analysis.priority, equals('High'));
      expect(analysis.confidence, equals(0.94));
    });

    test('AiSummary.fromJson defensive parsing', () {
      final json = {
        'problem': 'Server unreachable',
        'details': ['Ping timeout', 'Port 80 closed'],
        'actionsTaken': ['Power cycle executed'],
        'currentStatus': 'In Progress',
        'nextStep': 'Check hardware firewall',
      };

      final summary = AiSummary.fromJson(json);
      expect(summary.problem, equals('Server unreachable'));
      expect(summary.details.length, equals(2));
      expect(summary.actionsTaken.length, equals(1));
    });

    test('AiReply.fromJson defensive parsing isDraftOnly check', () {
      final json = {
        'draft': 'Draft response text',
        'action': 'generate',
        'isDraftOnly': true,
      };

      final reply = AiReply.fromJson(json);
      expect(reply.draft, equals('Draft response text'));
      expect(reply.isDraftOnly, isTrue);
    });
  });
}
