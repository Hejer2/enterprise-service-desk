import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../core/theme/app_theme.dart';
import '../../core/utils/error_handler.dart';
import '../../core/widgets/app_avatar.dart';
import '../../core/widgets/app_card.dart';
import '../../core/widgets/app_toast.dart';
import '../../models/user.dart';
import '../../providers/settings_provider.dart';
import '../auth/login_screen.dart';

class SettingsScreen extends ConsumerStatefulWidget {
  const SettingsScreen({super.key});

  @override
  ConsumerState<SettingsScreen> createState() => _SettingsScreenState();
}

class _SettingsScreenState extends ConsumerState<SettingsScreen> {
  String _activeTab = 'profile';

  void _onSelectTab(String id, String title) {
    if (id == 'users') {
      context.go('/users');
      return;
    }
    final isDesktop = MediaQuery.of(context).size.width >= 768;

    if (isDesktop) {
      setState(() => _activeTab = id);
    } else {
      Navigator.push(
        context,
        MaterialPageRoute(
          builder: (context) => SettingSubPage(
            title: title,
            content: _buildTabContent(id),
          ),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final isDesktop = MediaQuery.of(context).size.width >= 768;

    final textColor = isDark ? AppColors.darkTextPrimary : AppColors.textPrimary;
    final textSecColor = isDark ? AppColors.darkTextSecondary : AppColors.textSecondary;

    return SingleChildScrollView(
      padding: EdgeInsets.only(
        left: isDesktop ? 24 : 14,
        right: isDesktop ? 24 : 14,
        top: isDesktop ? 24 : 14,
        bottom: 24,
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Accounts Center',
                style: TextStyle(
                  fontSize: isDesktop ? 30 : 22,
                  fontWeight: FontWeight.w800,
                  color: textColor,
                ),
              ),
              const SizedBox(height: 4),
              Text(
                'Manage profile, credentials, notifications, and application settings.',
                style: TextStyle(
                  color: textSecColor,
                  fontSize: isDesktop ? 15 : 13,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),

          // Layout
          if (isDesktop)
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildDesktopTabBar(isDark),
                const SizedBox(height: 20),
                _buildTabContent(_activeTab),
              ],
            )
          else
            _buildSettingsSidebar(isDesktop, isDark),
        ],
      ),
    );
  }

  Widget _buildDesktopTabBar(bool isDark) {
    final currentUser = ref.watch(currentUserProvider);

    return AppCard(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: [
            _buildTabButton('profile', Icons.person_outline, 'Personal Details', isDark),
            const SizedBox(width: 8),
            _buildTabButton('security', Icons.lock_outline, 'Password & Security', isDark),
            const SizedBox(width: 8),
            _buildTabButton('preferences', Icons.palette_outlined, 'App Preferences', isDark),
            const SizedBox(width: 8),
            _buildTabButton('notifications', Icons.notifications_none, 'Notifications', isDark),
            const SizedBox(width: 8),
            _buildTabButton('help', Icons.help_outline, 'Help & Support', isDark),
            if (currentUser?.roleEntity?.name == 'ROLE_ADMIN') ...[
              const SizedBox(width: 8),
              _buildTabButton('users', Icons.people_outline, 'User Management', isDark),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildTabButton(String id, IconData icon, String title, bool isDark) {
    final isActive = _activeTab == id;
    const primaryColor = Color(0xFF6366F1);

    return InkWell(
      onTap: () => _onSelectTab(id, title),
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
        decoration: BoxDecoration(
          color: isActive ? primaryColor : Colors.transparent,
          borderRadius: BorderRadius.circular(10),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(
              icon,
              size: 18,
              color: isActive
                  ? Colors.white
                  : (isDark ? AppColors.darkTextSecondary : AppColors.textSecondary),
            ),
            const SizedBox(width: 8),
            Text(
              title,
              style: TextStyle(
                fontSize: 13.5,
                fontWeight: isActive ? FontWeight.bold : FontWeight.w600,
                color: isActive
                    ? Colors.white
                    : (isDark ? AppColors.darkTextPrimary : AppColors.textPrimary),
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSettingsSidebar(bool isDesktop, bool isDark) {
    final currentUser = ref.watch(currentUserProvider);

    return Column(
      children: [
        AppCard(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  AppAvatar(
                    profilePicture: currentUser?.profilePicture,
                    fullName: currentUser?.fullName ?? 'User Profile',
                    radius: 28,
                    onTap: () => showProfilePhotoPicker(context: context, ref: ref),
                  ),
                  Positioned(
                    right: -2,
                    bottom: -2,
                    child: InkWell(
                      onTap: () => showProfilePhotoPicker(context: context, ref: ref),
                      borderRadius: BorderRadius.circular(10),
                      child: Container(
                        padding: const EdgeInsets.all(4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF6366F1),
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 1.5),
                        ),
                        child: const Icon(Icons.camera_alt, color: Colors.white, size: 11),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 14),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      currentUser?.fullName ?? 'User Profile',
                      style: TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                        color: isDark ? AppColors.darkTextPrimary : AppColors.textPrimary,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      currentUser?.email ?? '',
                      style: TextStyle(
                        fontSize: 12,
                        color: isDark ? AppColors.darkTextSecondary : AppColors.textSecondary,
                      ),
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 6),
                    InkWell(
                      onTap: () => showProfilePhotoPicker(context: context, ref: ref),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: const Color(0xFF6366F1).withOpacity(0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.photo_camera_outlined, size: 12, color: Color(0xFF6366F1)),
                            SizedBox(width: 4),
                            Text(
                              'Change Photo',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF6366F1),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
        const SizedBox(height: 14),
        AppCard(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('ACCOUNT SETTINGS',
                  style: TextStyle(
                      fontSize: 11,
                      fontWeight: FontWeight.bold,
                      color: AppColors.textSecondary,
                      letterSpacing: 1.2)),
              const SizedBox(height: 14),
              _buildNavItem('profile', Icons.person_outline, 'Personal Details',
                  'Name, phone & profile picture', AppColors.info, isDesktop, isDark),
          const SizedBox(height: 8),
          _buildNavItem('security', Icons.lock_outline, 'Password & Security',
              'Change password & security log', AppColors.success, isDesktop, isDark),
          const SizedBox(height: 8),
          _buildNavItem(
              'preferences',
              Icons.palette_outlined,
              'App Preferences',
              'Theme mode & display language',
              AppColors.warning,
              isDesktop,
              isDark),
          const SizedBox(height: 8),
          _buildNavItem(
              'notifications',
              Icons.notifications_none,
              'Notifications',
              'Email & push notification toggles',
              AppColors.danger,
              isDesktop,
              isDark),
          const SizedBox(height: 8),
          _buildNavItem('help', Icons.help_outline, 'Help & Support',
              'FAQs and contact support', AppColors.primary, isDesktop, isDark),
          if (currentUser?.roleEntity?.name == 'ROLE_ADMIN') ...[
            const SizedBox(height: 8),
            _buildNavItem(
                'users',
                Icons.people_outline,
                'User Management',
                'Manage system users & role assignments',
                AppColors.primary,
                isDesktop,
                isDark),
          ],
        ],
      ),
    ),
  ],
);
}

  Widget _buildNavItem(String id, IconData icon, String title, String subtitle,
      Color color, bool isDesktop, bool isDark) {
    final isActive = isDesktop && _activeTab == id;
    final textColor = isDark ? AppColors.darkTextPrimary : AppColors.textPrimary;
    final textSecColor = isDark ? AppColors.darkTextSecondary : AppColors.textSecondary;

    return InkWell(
      onTap: () => _onSelectTab(id, title),
      borderRadius: BorderRadius.circular(AppRadius.button),
      child: Container(
        padding: const EdgeInsets.all(12),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(AppRadius.button),
          color: isActive ? AppColors.primaryLight : Colors.transparent,
          border: Border(
              left: BorderSide(
                  color: isActive ? AppColors.primary : Colors.transparent,
                  width: 4)),
        ),
        child: Row(
          children: [
            Container(
              width: 38,
              height: 38,
              alignment: Alignment.center,
              decoration: BoxDecoration(
                  color: color.withOpacity(0.12),
                  borderRadius: BorderRadius.circular(AppRadius.input)),
              child: Icon(icon, size: 20, color: color),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          color: textColor)),
                  Text(subtitle,
                      style: TextStyle(
                          fontSize: 11, color: textSecColor)),
                ],
              ),
            ),
            if (!isDesktop)
              Icon(Icons.chevron_right,
                  size: 20, color: textSecColor),
          ],
        ),
      ),
    );
  }

  Widget _buildTabContent(String tabId) {
    switch (tabId) {
      case 'profile':
        return const ProfileTabWidget();
      case 'security':
        return const SecurityTabWidget();
      case 'preferences':
        return const PreferencesTabWidget();
      case 'notifications':
        return const NotificationsTabWidget();
      case 'help':
        return const HelpTabWidget();
      default:
        return const SizedBox();
    }
  }
}

// ---------------------------------------------------------------------------
// 1. PROFILE TAB
// ---------------------------------------------------------------------------
class ProfileTabWidget extends ConsumerStatefulWidget {
  const ProfileTabWidget({super.key});

  @override
  ConsumerState<ProfileTabWidget> createState() => _ProfileTabWidgetState();
}

class _ProfileTabWidgetState extends ConsumerState<ProfileTabWidget> {
  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _phoneController = TextEditingController();
  bool _isLoading = false;
  String? _errorMessage;

  @override
  void initState() {
    super.initState();
    final currentUser = ref.read(currentUserProvider);
    _firstNameController.text = currentUser?.firstName ?? '';
    _lastNameController.text = currentUser?.lastName ?? '';
    _phoneController.text = currentUser?.phone ?? '';
  }

  @override
  void dispose() {
    _firstNameController.dispose();
    _lastNameController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  Future<void> _handleSaveProfile({String? newPhoto}) async {
    final firstName = _firstNameController.text.trim();
    final lastName = _lastNameController.text.trim();
    final phone = _phoneController.text.trim();
    final currentUser = ref.read(currentUserProvider);
    final profilePicture = newPhoto ?? currentUser?.profilePicture;

    if (firstName.isEmpty) {
      setState(() => _errorMessage = 'Please enter your first name.');
      return;
    }
    if (lastName.isEmpty) {
      setState(() => _errorMessage = 'Please enter your last name.');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final repo = ref.read(settingsRepositoryProvider);
      final res = await repo.updateProfile(
        firstName: firstName,
        lastName: lastName,
        phone: phone.isNotEmpty ? phone : null,
        profilePicture: profilePicture,
      );

      if (currentUser != null) {
        final updatedUser = User(
          id: currentUser.id,
          email: currentUser.email,
          firstName: res['firstName'] ?? firstName,
          lastName: res['lastName'] ?? lastName,
          phone: res['phone'] ?? phone,
          roleEntity: currentUser.roleEntity,
          language: currentUser.language,
          theme: currentUser.theme,
          profilePicture: res['profilePicture'] ?? profilePicture,
        );
        ref.read(currentUserProvider.notifier).state = updatedUser;
      }

      if (mounted) {
        AppToast.showSuccess(
          context,
          newPhoto != null
              ? 'Profile photo updated successfully!'
              : 'Personal details updated successfully.',
        );
      }
    } catch (e) {
      setState(() {
        _errorMessage = AppErrorHandler.getReadableErrorMessage(
          e,
          defaultMessage: 'Failed to update personal details.',
        );
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentUser = ref.watch(currentUserProvider);

    return AppCard(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('👤 Personal Details',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          const Divider(),
          const SizedBox(height: 16),
          Row(
            children: [
              Stack(
                clipBehavior: Clip.none,
                children: [
                  AppAvatar(
                    profilePicture: currentUser?.profilePicture,
                    fullName: currentUser?.fullName ?? 'User Profile',
                    radius: 34,
                    onTap: () => showProfilePhotoPicker(context: context, ref: ref),
                  ),
                  Positioned(
                    right: -2,
                    bottom: -2,
                    child: InkWell(
                      onTap: () => showProfilePhotoPicker(context: context, ref: ref),
                      borderRadius: BorderRadius.circular(12),
                      child: Container(
                        padding: const EdgeInsets.all(5),
                        decoration: BoxDecoration(
                          color: const Color(0xFF6366F1),
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 2),
                        ),
                        child: const Icon(Icons.camera_alt, color: Colors.white, size: 13),
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      currentUser?.fullName ?? 'User Profile',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16),
                    ),
                    const SizedBox(height: 4),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: const Color(0xFFEEF2FF),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Text(
                        currentUser?.roleEntity?.displayName ?? 'Employee',
                        style: const TextStyle(
                          fontSize: 11,
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF4F46E5),
                        ),
                      ),
                    ),
                    const SizedBox(height: 6),
                    InkWell(
                      onTap: () => showProfilePhotoPicker(context: context, ref: ref),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                        decoration: BoxDecoration(
                          color: const Color(0xFF6366F1).withOpacity(0.12),
                          borderRadius: BorderRadius.circular(6),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.photo_camera_outlined, size: 13, color: Color(0xFF6366F1)),
                            SizedBox(width: 5),
                            Text(
                              'Change Photo',
                              style: TextStyle(
                                fontSize: 12,
                                color: Color(0xFF6366F1),
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 24),
          if (_errorMessage != null) ...[
            Container(
              padding: const EdgeInsets.all(10),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: AppColors.dangerLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  const Icon(Icons.error_outline, size: 18, color: AppColors.danger),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _errorMessage!,
                      style: const TextStyle(color: AppColors.danger, fontSize: 12),
                    ),
                  ),
                ],
              ),
            ),
          ],
          Row(
            children: [
              Expanded(
                child: _buildInput('First Name', _firstNameController),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _buildInput('Last Name', _lastNameController),
              ),
            ],
          ),
          const SizedBox(height: 14),
          _buildInput('Email Address (Read-Only)',
              TextEditingController(text: currentUser?.email ?? ''),
              readOnly: true),
          const SizedBox(height: 14),
          _buildInput('Phone Number', _phoneController),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: _isLoading ? null : _handleSaveProfile,
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6366F1),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
              child: _isLoading
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                    )
                  : const Text('Save Changes',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildInput(String label, TextEditingController controller, {bool readOnly = false}) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(),
            style: const TextStyle(
                fontWeight: FontWeight.bold, fontSize: 11, color: Colors.grey)),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          readOnly: readOnly,
          style: TextStyle(
            fontSize: 14,
            color: isDark ? AppColors.darkTextPrimary : AppColors.textPrimary,
          ),
          decoration: InputDecoration(
            filled: true,
            fillColor: readOnly
                ? (isDark ? Colors.grey.shade900 : Colors.grey.shade100)
                : (isDark ? AppColors.darkSurface : AppColors.surface),
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
            border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.border)),
            enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.border)),
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// 2. SECURITY (CHANGE PASSWORD) TAB
// ---------------------------------------------------------------------------
class SecurityTabWidget extends ConsumerStatefulWidget {
  const SecurityTabWidget({super.key});

  @override
  ConsumerState<SecurityTabWidget> createState() => _SecurityTabWidgetState();
}

class _SecurityTabWidgetState extends ConsumerState<SecurityTabWidget> {
  // All password fields must start COMPLETELY EMPTY ("")
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _obscureCurrent = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;

  bool _isLoading = false;
  String? _errorMessage;

  @override
  void dispose() {
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  Future<void> _handleChangePassword() async {
    final currentPassword = _currentPasswordController.text;
    final newPassword = _newPasswordController.text;
    final confirmPassword = _confirmPasswordController.text;

    if (currentPassword.isEmpty) {
      setState(() => _errorMessage = 'Please enter your current password.');
      return;
    }
    if (newPassword.isEmpty) {
      setState(() => _errorMessage = 'Please enter a new password.');
      return;
    }
    if (confirmPassword.isEmpty) {
      setState(() => _errorMessage = 'Please confirm your new password.');
      return;
    }
    if (newPassword != confirmPassword) {
      setState(() => _errorMessage = 'New passwords do not match.');
      return;
    }
    if (newPassword.length < 8) {
      setState(() => _errorMessage = 'Password must be at least 8 characters long.');
      return;
    }

    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });

    try {
      final repo = ref.read(settingsRepositoryProvider);
      await repo.changePassword(
        currentPassword: currentPassword,
        newPassword: newPassword,
        confirmPassword: confirmPassword,
      );

      _currentPasswordController.clear();
      _newPasswordController.clear();
      _confirmPasswordController.clear();

      if (mounted) {
        AppToast.showSuccess(context, 'Password changed successfully.');
      }
    } catch (e) {
      setState(() {
        _errorMessage = AppErrorHandler.getReadableErrorMessage(
          e,
          defaultMessage: 'Failed to update password. Please check your credentials.',
        );
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return AppCard(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('🔒 Password & Security',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          const Divider(),
          const SizedBox(height: 16),
          if (_errorMessage != null) ...[
            Container(
              padding: const EdgeInsets.all(10),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: AppColors.dangerLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  const Icon(Icons.error_outline, size: 18, color: AppColors.danger),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _errorMessage!,
                      style: const TextStyle(color: AppColors.danger, fontSize: 12),
                    ),
                  ),
                ],
              ),
            ),
          ],
          _buildPasswordField(
            label: 'Current Password',
            controller: _currentPasswordController,
            obscureText: _obscureCurrent,
            onToggleVisibility: () => setState(() => _obscureCurrent = !_obscureCurrent),
          ),
          const SizedBox(height: 14),
          _buildPasswordField(
            label: 'New Password',
            controller: _newPasswordController,
            obscureText: _obscureNew,
            onToggleVisibility: () => setState(() => _obscureNew = !_obscureNew),
          ),
          const SizedBox(height: 14),
          _buildPasswordField(
            label: 'Confirm New Password',
            controller: _confirmPasswordController,
            obscureText: _obscureConfirm,
            onToggleVisibility: () => setState(() => _obscureConfirm = !_obscureConfirm),
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _isLoading ? null : _handleChangePassword,
              icon: _isLoading
                  ? const SizedBox()
                  : const Icon(Icons.key, size: 16),
              label: _isLoading
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                    )
                  : const Text('Update Password',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6366F1),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPasswordField({
    required String label,
    required TextEditingController controller,
    required bool obscureText,
    required VoidCallback onToggleVisibility,
  }) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label.toUpperCase(),
            style: const TextStyle(
                fontWeight: FontWeight.bold, fontSize: 11, color: Colors.grey)),
        const SizedBox(height: 6),
        TextField(
          controller: controller,
          obscureText: obscureText,
          style: TextStyle(
            fontSize: 14,
            color: isDark ? AppColors.darkTextPrimary : AppColors.textPrimary,
          ),
          decoration: InputDecoration(
            filled: true,
            fillColor: isDark ? AppColors.darkSurface : AppColors.surface,
            contentPadding:
                const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
            border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.border)),
            enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.border)),
            suffixIcon: IconButton(
              icon: Icon(
                obscureText
                    ? Icons.visibility_off_outlined
                    : Icons.visibility_outlined,
                color: isDark ? AppColors.darkTextSecondary : AppColors.textSecondary,
                size: 20,
              ),
              onPressed: onToggleVisibility,
            ),
          ),
        ),
      ],
    );
  }
}

// ---------------------------------------------------------------------------
// 3. PREFERENCES (THEME & LANGUAGE) TAB
// ---------------------------------------------------------------------------
class PreferencesTabWidget extends ConsumerStatefulWidget {
  const PreferencesTabWidget({super.key});

  @override
  ConsumerState<PreferencesTabWidget> createState() => _PreferencesTabWidgetState();
}

class _PreferencesTabWidgetState extends ConsumerState<PreferencesTabWidget> {
  Future<void> _updateTheme(String val) async {
    final mode = val == 'dark' ? ThemeMode.dark : ThemeMode.light;
    await ref.read(themeModeProvider.notifier).setThemeMode(mode);
    try {
      await ref.read(settingsRepositoryProvider).updatePreferences(theme: val);
    } catch (_) {}
    if (mounted) {
      AppToast.showSuccess(context, 'Theme mode updated successfully.');
    }
  }

  Future<void> _updateLanguage(String val) async {
    await ref.read(appLocaleProvider.notifier).setLocale(val);
    try {
      await ref.read(settingsRepositoryProvider).updatePreferences(language: val);
    } catch (_) {}
    if (mounted) {
      AppToast.showSuccess(context, 'Language updated successfully.');
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentThemeMode = ref.watch(themeModeProvider);
    final currentLocale = ref.watch(appLocaleProvider);
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return AppCard(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('🎨 App Preferences',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          const Divider(),
          const SizedBox(height: 16),
          const Text('DISPLAY MODE',
              style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 11,
                  color: Colors.grey)),
          const SizedBox(height: 6),
          DropdownButtonFormField<String>(
            decoration: InputDecoration(
              filled: true,
              fillColor: isDark ? AppColors.darkSurface : AppColors.surface,
              contentPadding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.border)),
              enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.border)),
            ),
            value: currentThemeMode == ThemeMode.dark ? 'dark' : 'light',
            items: const [
              DropdownMenuItem(
                  value: 'light',
                  child: Text('Light Mode', style: TextStyle(fontSize: 13))),
              DropdownMenuItem(
                  value: 'dark',
                  child: Text('Dark Mode', style: TextStyle(fontSize: 13))),
            ],
            onChanged: (val) {
              if (val != null) {
                _updateTheme(val);
              }
            },
          ),
          const SizedBox(height: 16),
          const Text('Language',
              style: TextStyle(
                  fontWeight: FontWeight.bold,
                  fontSize: 11,
                  color: Colors.grey)),
          const SizedBox(height: 6),
          DropdownButtonFormField<String>(
            decoration: InputDecoration(
              filled: true,
              fillColor: isDark ? AppColors.darkSurface : AppColors.surface,
              contentPadding:
                  const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.border)),
              enabledBorder: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(12),
                  borderSide: BorderSide(color: isDark ? AppColors.darkBorder : AppColors.border)),
            ),
            value: currentLocale.languageCode == 'ar' ? 'ar' : 'en',
            items: const [
              DropdownMenuItem(
                  value: 'en',
                  child: Text('English (EN)', style: TextStyle(fontSize: 13))),
              DropdownMenuItem(
                  value: 'ar',
                  child: Text('العربية (AR)', style: TextStyle(fontSize: 13))),
            ],
            onChanged: (val) {
              if (val != null) {
                _updateLanguage(val);
              }
            },
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                AppToast.showSuccess(context, 'Preferences are active and saved.');
              },
              icon: const Icon(Icons.check_circle_outline, size: 16),
              label: const Text('Preferences Active',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6366F1),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 4. NOTIFICATIONS PREFERENCES TAB
// ---------------------------------------------------------------------------
class NotificationsTabWidget extends ConsumerStatefulWidget {
  const NotificationsTabWidget({super.key});

  @override
  ConsumerState<NotificationsTabWidget> createState() => _NotificationsTabWidgetState();
}

class _NotificationsTabWidgetState extends ConsumerState<NotificationsTabWidget> {
  bool _isLoading = true;
  bool _isSaving = false;
  String? _errorMessage;

  bool _ticketAssignments = true;
  bool _ticketReplies = true;
  bool _ticketStatusChanges = true;
  bool _slaAlerts = true;
  bool _systemNotifications = true;
  bool _browserNotifications = true;

  @override
  void initState() {
    super.initState();
    _loadPreferences();
  }

  Future<void> _loadPreferences() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
    });
    try {
      final repo = ref.read(settingsRepositoryProvider);
      final data = await repo.getNotificationPreferences();
      if (mounted) {
        setState(() {
          _ticketAssignments = data['ticketAssignments'] ?? true;
          _ticketReplies = data['ticketReplies'] ?? true;
          _ticketStatusChanges = data['ticketStatusChanges'] ?? true;
          _slaAlerts = data['slaAlerts'] ?? true;
          _systemNotifications = data['systemNotifications'] ?? true;
          _browserNotifications = data['browserNotifications'] ?? true;
          _isLoading = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = AppErrorHandler.getReadableErrorMessage(
            e,
            defaultMessage: 'Unable to load notification preferences.',
          );
          _isLoading = false;
        });
      }
    }
  }

  Future<void> _handleSavePreferences() async {
    setState(() {
      _isSaving = true;
      _errorMessage = null;
    });
    try {
      final repo = ref.read(settingsRepositoryProvider);
      await repo.saveNotificationPreferences({
        'ticketAssignments': _ticketAssignments,
        'ticketReplies': _ticketReplies,
        'ticketStatusChanges': _ticketStatusChanges,
        'slaAlerts': _slaAlerts,
        'systemNotifications': _systemNotifications,
        'browserNotifications': _browserNotifications,
      });
      if (mounted) {
        AppToast.showSuccess(context, 'Notification settings saved successfully.');
      }
    } catch (e) {
      setState(() {
        _errorMessage = AppErrorHandler.getReadableErrorMessage(
          e,
          defaultMessage: 'Failed to save notification settings.',
        );
      });
    } finally {
      if (mounted) setState(() => _isSaving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const AppCard(
        padding: EdgeInsets.all(32),
        child: Center(
          child: CircularProgressIndicator(),
        ),
      );
    }

    return AppCard(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('🔔 Notification Preferences',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          const Divider(),
          const SizedBox(height: 16),
          if (_errorMessage != null) ...[
            Container(
              padding: const EdgeInsets.all(10),
              margin: const EdgeInsets.only(bottom: 16),
              decoration: BoxDecoration(
                color: AppColors.dangerLight,
                borderRadius: BorderRadius.circular(8),
              ),
              child: Row(
                children: [
                  const Icon(Icons.error_outline, size: 18, color: AppColors.danger),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      _errorMessage!,
                      style: const TextStyle(color: AppColors.danger, fontSize: 12),
                    ),
                  ),
                ],
              ),
            ),
          ],
          _buildToggle(
            'Ticket Assignments',
            'Receive alerts when a ticket is assigned to you.',
            _ticketAssignments,
            (val) => setState(() => _ticketAssignments = val),
          ),
          const SizedBox(height: 12),
          _buildToggle(
            'Ticket Replies',
            'Receive alerts when a customer or agent replies to your ticket.',
            _ticketReplies,
            (val) => setState(() => _ticketReplies = val),
          ),
          const SizedBox(height: 12),
          _buildToggle(
            'Status Changes',
            'Receive alerts when ticket status is updated or resolved.',
            _ticketStatusChanges,
            (val) => setState(() => _ticketStatusChanges = val),
          ),
          const SizedBox(height: 12),
          _buildToggle(
            'SLA Breach Alerts',
            'Receive critical notifications for tickets approaching SLA breach.',
            _slaAlerts,
            (val) => setState(() => _slaAlerts = val),
          ),
          const SizedBox(height: 12),
          _buildToggle(
            'System Announcements',
            'Receive updates on maintenance schedules and platform notices.',
            _systemNotifications,
            (val) => setState(() => _systemNotifications = val),
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: _isSaving ? null : _handleSavePreferences,
              icon: _isSaving
                  ? const SizedBox()
                  : const Icon(Icons.save, size: 16),
              label: _isSaving
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                    )
                  : const Text('Save Notifications',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF6366F1),
                foregroundColor: Colors.white,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildToggle(String title, String subtitle, bool value, ValueChanged<bool> onChanged) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final textColor = isDark ? AppColors.darkTextPrimary : AppColors.textPrimary;
    final textSecColor = isDark ? AppColors.darkTextSecondary : AppColors.textSecondary;

    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: isDark ? AppColors.darkSurface : const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: isDark ? AppColors.darkBorder : const Color(0xFFE2E8F0)),
      ),
      child: Row(
        children: [
          Checkbox(
            value: value,
            activeColor: const Color(0xFF6366F1),
            onChanged: (v) {
              if (v != null) onChanged(v);
            },
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title,
                    style: TextStyle(
                        fontWeight: FontWeight.bold, fontSize: 13, color: textColor)),
                Text(subtitle,
                    style: TextStyle(color: textSecColor, fontSize: 11)),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 5. HELP TAB
// ---------------------------------------------------------------------------
class HelpTabWidget extends StatelessWidget {
  const HelpTabWidget({super.key});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;
    final textColor = isDark ? AppColors.darkTextPrimary : AppColors.textPrimary;
    final textSecColor = isDark ? AppColors.darkTextSecondary : AppColors.textSecondary;

    return AppCard(
      padding: const EdgeInsets.all(20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text('❓ Help & Support',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 12),
          const Divider(),
          const SizedBox(height: 16),
          Text('How are ticket priorities routed?',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor)),
          const SizedBox(height: 4),
          Text(
              'Tickets are auto-routed based on the category chosen (IT Support, Machine Maintenance, etc.).',
              style: TextStyle(color: textSecColor, fontSize: 13)),
          const SizedBox(height: 16),
          Text('What are the SLA guidelines?',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: textColor)),
          const SizedBox(height: 4),
          Text(
              'Under normal operations, Critical tickets target resolution within 2 hours.',
              style: TextStyle(color: textSecColor, fontSize: 13)),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton.icon(
              onPressed: () {
                AppToast.showInfo(context, 'Support guide is available in Knowledge Base.');
              },
              icon: const Icon(Icons.menu_book_outlined, size: 16),
              label: const Text('Browse Knowledge Base Articles',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
              style: ElevatedButton.styleFrom(
                backgroundColor: isDark ? AppColors.darkSurface : AppColors.surface,
                foregroundColor: textColor,
                side: BorderSide(
                    color: isDark ? AppColors.darkBorder : AppColors.border),
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12)),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ---------------------------------------------------------------------------
// 6. SETTING SUB PAGE (MOBILE VIEW)
// ---------------------------------------------------------------------------
class SettingSubPage extends StatelessWidget {
  final String title;
  final Widget content;

  const SettingSubPage({super.key, required this.title, required this.content});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    return Scaffold(
      backgroundColor: isDark ? AppColors.darkBgApp : AppColors.bgApp,
      appBar: AppBar(
        title: Text(title,
            style: TextStyle(
              fontWeight: FontWeight.bold,
              fontSize: 18,
              color: isDark ? AppColors.darkTextPrimary : AppColors.textPrimary,
            )),
        backgroundColor: isDark ? AppColors.darkSurface : AppColors.surface,
        elevation: 0,
        iconTheme: IconThemeData(
          color: isDark ? AppColors.darkTextPrimary : AppColors.textPrimary,
        ),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: content,
      ),
    );
  }
}
