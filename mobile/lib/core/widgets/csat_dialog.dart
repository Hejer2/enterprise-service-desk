import 'package:flutter/material.dart';
import '../theme/app_theme.dart';
import 'app_button.dart';

class CsatDialog extends StatefulWidget {
  final String ticketNumber;
  final Function(int rating, String? comment) onSubmit;
  final VoidCallback onSkip;

  const CsatDialog({
    super.key,
    required this.ticketNumber,
    required this.onSubmit,
    required this.onSkip,
  });

  @override
  State<CsatDialog> createState() => _CsatDialogState();
}

class _CsatDialogState extends State<CsatDialog> {
  int _rating = 5;
  final TextEditingController _commentController = TextEditingController();

  @override
  void dispose() {
    _commentController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Dialog(
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(AppRadius.modal),
      ),
      child: Padding(
        padding: const EdgeInsets.all(AppSpacing.lg),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const CircleAvatar(
              radius: 28,
              backgroundColor: AppColors.warningLight,
              child: Icon(Icons.star_rounded, size: 36, color: AppColors.warning),
            ),
            const SizedBox(height: AppSpacing.md),
            const Text(
              'Support Experience',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: AppColors.textPrimary,
              ),
            ),
            const SizedBox(height: AppSpacing.xs),
            Text(
              'How satisfied are you with the resolution for ticket ${widget.ticketNumber}?',
              textAlign: TextAlign.center,
              style: const TextStyle(
                fontSize: 13,
                color: AppColors.textSecondary,
              ),
            ),
            const SizedBox(height: AppSpacing.lg),

            // 1-5 Star Interactive Selector
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(5, (index) {
                final starValue = index + 1;
                return IconButton(
                  icon: Icon(
                    starValue <= _rating ? Icons.star_rounded : Icons.star_outline_rounded,
                    size: 36,
                    color: starValue <= _rating ? AppColors.warning : Colors.grey.shade400,
                  ),
                  onPressed: () => setState(() => _rating = starValue),
                );
              }),
            ),
            const SizedBox(height: AppSpacing.md),

            // Optional Comment Field
            TextField(
              controller: _commentController,
              maxLines: 3,
              decoration: InputDecoration(
                hintText: 'Tell us more (optional feedback)...',
                hintStyle: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                contentPadding: const EdgeInsets.all(AppSpacing.sm),
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(AppRadius.input),
                  borderSide: const BorderSide(color: AppColors.border),
                ),
              ),
            ),
            const SizedBox(height: AppSpacing.lg),

            Row(
              children: [
                Expanded(
                  child: TextButton(
                    onPressed: widget.onSkip,
                    child: const Text('Skip for now', style: TextStyle(color: AppColors.textSecondary)),
                  ),
                ),
                const SizedBox(width: AppSpacing.sm),
                Expanded(
                  child: AppButton(
                    text: 'Submit Rating',
                    onPressed: () {
                      final comment = _commentController.text.trim();
                      widget.onSubmit(_rating, comment.isNotEmpty ? comment : null);
                    },
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

Future<void> showCsatDialog({
  required BuildContext context,
  required String ticketNumber,
  required Function(int rating, String? comment) onSubmit,
  required VoidCallback onSkip,
}) {
  return showDialog(
    context: context,
    barrierDismissible: false,
    builder: (context) => CsatDialog(
      ticketNumber: ticketNumber,
      onSubmit: onSubmit,
      onSkip: onSkip,
    ),
  );
}
