import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:image_picker/image_picker.dart';
import '../theme/app_theme.dart';
import '../utils/error_handler.dart';
import '../widgets/app_toast.dart';
import '../../models/user.dart';
import '../../providers/settings_provider.dart';
import '../../screens/auth/login_screen.dart';

class AppAvatar extends StatelessWidget {
  final String? profilePicture;
  final String fullName;
  final double radius;
  final VoidCallback? onTap;

  const AppAvatar({
    super.key,
    this.profilePicture,
    required this.fullName,
    this.radius = 24,
    this.onTap,
  });

  static const List<Map<String, dynamic>> avatarPresets = [
    {
      'id': 'preset:tech',
      'label': 'Technician',
      'icon': Icons.engineering_rounded,
      'color': Color(0xFF2563EB),
    },
    {
      'id': 'preset:support',
      'label': 'Support',
      'icon': Icons.headset_mic_rounded,
      'color': Color(0xFF10B981),
    },
    {
      'id': 'preset:developer',
      'label': 'Developer',
      'icon': Icons.code_rounded,
      'color': Color(0xFF6366F1),
    },
    {
      'id': 'preset:security',
      'label': 'Security',
      'icon': Icons.security_rounded,
      'color': Color(0xFFEF4444),
    },
    {
      'id': 'preset:manager',
      'label': 'Manager',
      'icon': Icons.business_center_rounded,
      'color': Color(0xFFF59E0B),
    },
    {
      'id': 'preset:admin',
      'label': 'Admin',
      'icon': Icons.admin_panel_settings_rounded,
      'color': Color(0xFF8B5CF6),
    },
    {
      'id': 'preset:analyst',
      'label': 'Analyst',
      'icon': Icons.insights_rounded,
      'color': Color(0xFF06B6D4),
    },
    {
      'id': 'preset:user',
      'label': 'Employee',
      'icon': Icons.person_rounded,
      'color': Color(0xFF64748B),
    },
  ];

  @override
  Widget build(BuildContext context) {
    Widget avatarWidget;
    final initial = fullName.trim().isNotEmpty
        ? fullName.trim().substring(0, 1).toUpperCase()
        : 'U';

    if (profilePicture != null && profilePicture!.startsWith('preset:')) {
      final preset = avatarPresets.firstWhere(
        (p) => p['id'] == profilePicture,
        orElse: () => avatarPresets.last,
      );
      final Color bg = preset['color'] as Color;
      final IconData icon = preset['icon'] as IconData;

      avatarWidget = CircleAvatar(
        radius: radius,
        backgroundColor: bg.withOpacity(0.2),
        child: Icon(icon, color: bg, size: radius * 1.1),
      );
    } else if (profilePicture != null &&
        (profilePicture!.startsWith('http://') ||
            profilePicture!.startsWith('https://') ||
            profilePicture!.startsWith('/uploads/'))) {
      final String imgUrl = profilePicture!.startsWith('/uploads/')
          ? 'http://127.0.0.1:8000$profilePicture'
          : profilePicture!;

      avatarWidget = CircleAvatar(
        radius: radius,
        backgroundColor: AppColors.primaryLight,
        backgroundImage: NetworkImage(imgUrl),
        onBackgroundImageError: (_, __) {},
        child: Text(
          initial,
          style: TextStyle(
            fontSize: radius * 0.8,
            fontWeight: FontWeight.bold,
            color: AppColors.primary,
          ),
        ),
      );
    } else if (profilePicture != null && profilePicture!.startsWith('data:image/')) {
      try {
        final base64String = profilePicture!.split(',').last;
        final bytes = base64Decode(base64String);
        avatarWidget = CircleAvatar(
          radius: radius,
          backgroundColor: AppColors.primaryLight,
          backgroundImage: MemoryImage(bytes),
          onBackgroundImageError: (_, __) {},
          child: Text(
            initial,
            style: TextStyle(
              fontSize: radius * 0.8,
              fontWeight: FontWeight.bold,
              color: AppColors.primary,
            ),
          ),
        );
      } catch (_) {
        avatarWidget = CircleAvatar(
          radius: radius,
          backgroundColor: const Color(0xFF6366F1).withOpacity(0.15),
          child: Text(
            initial,
            style: TextStyle(
              fontSize: radius * 0.8,
              fontWeight: FontWeight.bold,
              color: const Color(0xFF6366F1),
            ),
          ),
        );
      }
    } else {
      avatarWidget = CircleAvatar(
        radius: radius,
        backgroundColor: const Color(0xFF6366F1).withOpacity(0.15),
        child: Text(
          initial,
          style: TextStyle(
            fontSize: radius * 0.8,
            fontWeight: FontWeight.bold,
            color: const Color(0xFF6366F1),
          ),
        ),
      );
    }

    if (onTap != null) {
      return InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(radius),
        child: avatarWidget,
      );
    }

    return avatarWidget;
  }
}

Future<void> showProfilePhotoPicker({
  required BuildContext context,
  required WidgetRef ref,
  VoidCallback? onPhotoUpdated,
}) async {
  final currentUser = ref.read(currentUserProvider);
  final picker = ImagePicker();

  Future<void> pickAndUploadImage(ImageSource source) async {
    try {
      final XFile? pickedFile = await picker.pickImage(
        source: source,
        maxWidth: 800,
        maxHeight: 800,
        imageQuality: 85,
      );
      if (pickedFile != null) {
        final bytes = await pickedFile.readAsBytes();
        final base64String = base64Encode(bytes);
        final mimeType = pickedFile.name.endsWith('.png') ? 'image/png' : 'image/jpeg';
        final dataUri = 'data:$mimeType;base64,$base64String';

        if (context.mounted) {
          await _saveSelectedPhoto(context, ref, dataUri);
          onPhotoUpdated?.call();
        }
      }
    } catch (e) {
      if (context.mounted) {
        AppToast.showError(
          context,
          'Could not access ${source == ImageSource.camera ? 'camera' : 'gallery'}.',
        );
      }
    }
  }

  await showModalBottomSheet(
    context: context,
    isScrollControlled: true,
    shape: const RoundedRectangleBorder(
      borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
    ),
    builder: (ctx) {
      return Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          top: 20,
          bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
        ),
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.add_a_photo_outlined,
                          color: Color(0xFF6366F1), size: 22),
                      SizedBox(width: 8),
                      Text(
                        'Change Profile Photo',
                        style: TextStyle(
                            fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                    ],
                  ),
                  IconButton(
                    icon: const Icon(Icons.close),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
              const SizedBox(height: 16),

              // Action Buttons: Camera & Gallery
              Row(
                children: [
                  Expanded(
                    child: InkWell(
                      onTap: () async {
                        Navigator.pop(ctx);
                        await pickAndUploadImage(ImageSource.camera);
                      },
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        decoration: BoxDecoration(
                          color: const Color(0xFF6366F1).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFF6366F1).withOpacity(0.3)),
                        ),
                        child: const Column(
                          children: [
                            Icon(Icons.camera_alt_rounded, color: Color(0xFF6366F1), size: 28),
                            SizedBox(height: 6),
                            Text(
                              'Take Photo',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF6366F1),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: InkWell(
                      onTap: () async {
                        Navigator.pop(ctx);
                        await pickAndUploadImage(ImageSource.gallery);
                      },
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                        decoration: BoxDecoration(
                          color: const Color(0xFF10B981).withOpacity(0.1),
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: const Color(0xFF10B981).withOpacity(0.3)),
                        ),
                        child: const Column(
                          children: [
                            Icon(Icons.photo_library_rounded, color: Color(0xFF10B981), size: 28),
                            SizedBox(height: 6),
                            Text(
                              'Choose from Gallery',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF10B981),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 20),
              const Divider(),
              const SizedBox(height: 12),

              const Text(
                'Or choose an Avatar style:',
                style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13),
              ),
              const SizedBox(height: 12),
              GridView.builder(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 4,
                  crossAxisSpacing: 10,
                  mainAxisSpacing: 10,
                ),
                itemCount: AppAvatar.avatarPresets.length,
                itemBuilder: (context, idx) {
                  final preset = AppAvatar.avatarPresets[idx];
                  final isSelected =
                      currentUser?.profilePicture == preset['id'];
                  final Color color = preset['color'] as Color;
                  final IconData icon = preset['icon'] as IconData;

                  return InkWell(
                    onTap: () async {
                      Navigator.pop(ctx);
                      await _saveSelectedPhoto(
                          context, ref, preset['id'] as String);
                      onPhotoUpdated?.call();
                    },
                    borderRadius: BorderRadius.circular(12),
                    child: Container(
                      decoration: BoxDecoration(
                        color: isSelected
                            ? color.withOpacity(0.25)
                            : color.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(
                          color: isSelected ? color : Colors.transparent,
                          width: 2,
                        ),
                      ),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(icon, color: color, size: 24),
                          const SizedBox(height: 4),
                          Text(
                            preset['label'] as String,
                            style: TextStyle(
                              fontSize: 10,
                              fontWeight: FontWeight.w600,
                              color: color,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ],
                      ),
                    ),
                  );
                },
              ),
            ],
          ),
        ),
      );
    },
  );
}

Future<void> _saveSelectedPhoto(
    BuildContext context, WidgetRef ref, String photo) async {
  final currentUser = ref.read(currentUserProvider);
  if (currentUser == null) return;

  try {
    final repo = ref.read(settingsRepositoryProvider);
    final res = await repo.updateProfile(
      firstName: currentUser.firstName,
      lastName: currentUser.lastName,
      phone: currentUser.phone,
      profilePicture: photo,
    );

    final updatedUser = User(
      id: currentUser.id,
      email: currentUser.email,
      firstName: res['firstName'] ?? currentUser.firstName,
      lastName: res['lastName'] ?? currentUser.lastName,
      phone: res['phone'] ?? currentUser.phone,
      roleEntity: currentUser.roleEntity,
      language: currentUser.language,
      theme: currentUser.theme,
      profilePicture: res['profilePicture'] ?? photo,
    );
    ref.read(currentUserProvider.notifier).state = updatedUser;
    if (context.mounted) {
      AppToast.showSuccess(context, 'Profile photo updated successfully!');
    }
  } catch (e) {
    if (context.mounted) {
      AppToast.showError(
        context,
        AppErrorHandler.getReadableErrorMessage(
          e,
          defaultMessage: 'Failed to update profile photo.',
        ),
      );
    }
  }
}
