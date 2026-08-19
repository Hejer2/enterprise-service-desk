import 'dart:io';
import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class AttachmentPreviewGrid extends StatelessWidget {
  final List<File> files;
  final Function(int index) onRemove;

  const AttachmentPreviewGrid({
    super.key,
    required this.files,
    required this.onRemove,
  });

  bool _isImage(String path) {
    final lower = path.toLowerCase();
    return lower.endsWith('.jpg') ||
        lower.endsWith('.jpeg') ||
        lower.endsWith('.png') ||
        lower.endsWith('.webp') ||
        lower.endsWith('.gif');
  }

  String _formatFileSize(int bytes) {
    if (bytes < 1024) return '$bytes B';
    if (bytes < 1024 * 1024) return '${(bytes / 1024).toStringAsFixed(1)} KB';
    return '${(bytes / (1024 * 1024)).toStringAsFixed(1)} MB';
  }

  @override
  Widget build(BuildContext context) {
    if (files.isEmpty) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.only(top: AppSpacing.sm),
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        children: List.generate(files.length, (index) {
          final file = files[index];
          final isImg = _isImage(file.path);
          final fileName = file.path.split(Platform.pathSeparator).last;
          final fileSize = file.existsSync() ? _formatFileSize(file.lengthSync()) : '';

          return Container(
            width: 140,
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(AppRadius.input),
              border: Border.all(color: AppColors.border),
            ),
            child: Stack(
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      height: 70,
                      width: double.infinity,
                      decoration: BoxDecoration(
                        color: AppColors.bgApp,
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: isImg
                          ? ClipRRect(
                              borderRadius: BorderRadius.circular(6),
                              child: Image.file(file, fit: BoxFit.cover),
                            )
                          : const Center(
                              child: Icon(Icons.insert_drive_file_outlined,
                                  color: AppColors.textSecondary, size: 28),
                            ),
                    ),
                    const SizedBox(height: 6),
                    Text(
                      fileName,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: AppColors.textPrimary),
                    ),
                    Text(
                      fileSize,
                      style: const TextStyle(
                          fontSize: 10, color: AppColors.textSecondary),
                    ),
                  ],
                ),
                Positioned(
                  right: 0,
                  top: 0,
                  child: InkWell(
                    onTap: () => onRemove(index),
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      padding: const EdgeInsets.all(2),
                      decoration: const BoxDecoration(
                        color: AppColors.danger,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.close,
                          size: 14, color: Colors.white),
                    ),
                  ),
                ),
              ],
            ),
          );
        }),
      ),
    );
  }
}
