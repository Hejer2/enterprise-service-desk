import 'package:flutter/material.dart';

class SlaStatusBadge extends StatelessWidget {
  final String status;
  final bool isCompact;

  const SlaStatusBadge({
    super.key,
    required this.status,
    this.isCompact = false,
  });

  @override
  Widget build(BuildContext context) {
    final normalized = status.toUpperCase().replaceAll(' ', '_');
    Color bg;
    Color fg;
    String label;

    switch (normalized) {
      case 'COMPLETED':
        bg = const Color(0xFFDCFCE7);
        fg = const Color(0xFF15803D);
        label = 'COMPLETED';
        break;
      case 'BREACHED':
        bg = const Color(0xFFFEE2E2);
        fg = const Color(0xFFB91C1C);
        label = 'BREACHED';
        break;
      case 'AT_RISK':
        bg = const Color(0xFFFFEDD5);
        fg = const Color(0xFFC2410C);
        label = 'AT RISK';
        break;
      case 'PAUSED':
        bg = const Color(0xFFFEF3C7);
        fg = const Color(0xFFB45309);
        label = 'PAUSED';
        break;
      case 'ACTIVE':
      default:
        bg = const Color(0xFFEFF6FF);
        fg = const Color(0xFF2563EB);
        label = 'ACTIVE';
        break;
    }

    return Semantics(
      label: 'SLA Status: $label',
      child: Container(
        padding: EdgeInsets.symmetric(
          horizontal: isCompact ? 6 : 10,
          vertical: isCompact ? 2 : 4,
        ),
        decoration: BoxDecoration(
          color: bg,
          borderRadius: BorderRadius.circular(6),
          border: Border.all(color: fg.withOpacity(0.3)),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: fg,
            fontSize: isCompact ? 10 : 11,
            fontWeight: FontWeight.bold,
            letterSpacing: 0.3,
          ),
        ),
      ),
    );
  }
}
