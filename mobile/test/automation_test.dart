import 'package:flutter_test/flutter_test.dart';
import 'package:mobile/models/approval_request.dart';

void main() {
  group('Phase 3D Automation & Approval Tests', () {
    test('ApprovalRequest.fromJson defensive parsing', () {
      final json = {
        'id': 7,
        'ticketId': 105,
        'ticketNumber': 'TCK-105',
        'ticketTitle': 'Software License Request',
        'requestedBy': 'John Doe',
        'reason': 'Need IDE license for project',
        'status': 'PENDING',
        'requestedAt': '2026-08-17 15:40:00',
      };

      final req = ApprovalRequest.fromJson(json);
      expect(req.id, equals(7));
      expect(req.ticketId, equals(105));
      expect(req.ticketNumber, equals('TCK-105'));
      expect(req.status, equals('PENDING'));
    });
  });
}
