import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/models/executive_analytics.dart';

void main() {
  group('ExecutiveAnalytics Defensive Parsing Tests', () {
    test('ExecutiveAnalytics.fromJson defensive parsing', () {
      final json = {
        'kpis': {
          'totalTickets': 120,
          'openTickets': 15,
          'resolvedTickets': 90,
          'closedTickets': 15,
          'slaCompliancePct': 98.5,
          'slaBreaches': 2,
          'avgCsat': 4.75,
          'csatRatingsCount': 40,
          'automationExecutions': 350,
        },
        'technicians': [
          {
            'id': 1,
            'name': 'Alex Technician',
            'assigned': 50,
            'resolved': 48,
            'completionRate': 96.0,
          }
        ]
      };

      final analytics = ExecutiveAnalytics.fromJson(json);
      expect(analytics.totalTickets, equals(120));
      expect(analytics.openTickets, equals(15));
      expect(analytics.slaCompliancePct, equals(98.5));
      expect(analytics.avgCsat, equals(4.75));
      expect(analytics.automationExecutions, equals(350));
      expect(analytics.technicians.length, equals(1));
      expect(analytics.technicians.first.name, equals('Alex Technician'));
    });
  });
}
