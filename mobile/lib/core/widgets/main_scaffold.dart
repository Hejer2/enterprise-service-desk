import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../theme/app_theme.dart';
import '../widgets/app_avatar.dart';
import '../widgets/app_button.dart';
import '../../models/user.dart';
import '../../screens/auth/login_screen.dart';
import '../../providers/notification_providers.dart';

class MainScaffold extends ConsumerStatefulWidget {
  final Widget child;

  const MainScaffold({super.key, required this.child});

  @override
  ConsumerState<MainScaffold> createState() => _MainScaffoldState();
}

class _MainScaffoldState extends ConsumerState<MainScaffold> {
  int _calculateSelectedIndex(BuildContext context) {
    try {
      final String location = GoRouterState.of(context).uri.path;
      if (location.startsWith('/dashboard')) return 0;
      if (location.startsWith('/tickets')) return 1;
      if (location.startsWith('/notifications')) return 2;
      if (location.startsWith('/reports')) return 3;
      if (location.startsWith('/settings')) return 4;
      if (location.startsWith('/users')) return 5;
    } catch (_) {}
    return 0;
  }

  void _onItemTapped(int index) {
    switch (index) {
      case 0:
        context.go('/dashboard');
        break;
      case 1:
        context.go('/tickets');
        break;
      case 2:
        context.go('/notifications');
        break;
      case 3:
        context.go('/reports');
        break;
      case 4:
        context.go('/settings');
        break;
      case 5:
        context.go('/users');
        break;
    }
  }

  void _confirmLogout() {
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    showDialog(
      context: context,
      builder: (dialogContext) => AlertDialog(
        shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(AppRadius.modal)),
        backgroundColor: isDark ? AppColors.darkSurface : AppColors.surface,
        title: Row(
          children: [
            const Icon(Icons.logout, color: AppColors.danger, size: 22),
            const SizedBox(width: AppSpacing.sm),
            Text('Log Out',
                style: TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 18,
                    color: isDark
                        ? AppColors.darkTextPrimary
                        : AppColors.textPrimary)),
          ],
        ),
        content: Text(
          'Are you sure you want to securely log out of your session?',
          style: TextStyle(
            color: isDark
                ? AppColors.darkTextSecondary
                : AppColors.textSecondary,
          ),
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(dialogContext),
            child: Text('Cancel',
                style: TextStyle(
                    color: isDark
                        ? AppColors.darkTextSecondary
                        : AppColors.textSecondary)),
          ),
          AppButton(
            text: 'Log Out',
            variant: AppButtonVariant.danger,
            onPressed: () async {
              Navigator.pop(dialogContext);
              final repo = ref.read(authRepositoryProvider);
              await repo.logout();
              ref.read(authProvider.notifier).state = false;
              ref.read(currentUserProvider.notifier).state = null;
              if (mounted) context.go('/login');
            },
          ),
        ],
      ),
    );
  }

  Widget _buildNotificationBadge({
    required Widget icon,
    required int unreadCount,
  }) {
    if (unreadCount <= 0) {
      return icon;
    }

    final badgeText = unreadCount > 99 ? '99+' : '$unreadCount';

    return Badge(
      label: Text(
        badgeText,
        style: const TextStyle(
          color: Colors.white,
          fontSize: 10,
          fontWeight: FontWeight.bold,
        ),
      ),
      backgroundColor: const Color(0xFFEF4444),
      child: icon,
    );
  }

  @override
  Widget build(BuildContext context) {
    final selectedIndex = _calculateSelectedIndex(context);
    final isDesktop = MediaQuery.of(context).size.width >= 768;
    final currentUser = ref.watch(currentUserProvider);
    final unreadCount = ref.watch(unreadNotificationsCountProvider);
    final role = currentUser?.roleEntity?.name;
    final fullName = currentUser?.fullName ?? 'User';
    final roleDisplayName = currentUser?.roleEntity?.displayName ?? 'Employee';
    final initial = fullName.isNotEmpty ? fullName.substring(0, 1) : 'U';
    final theme = Theme.of(context);
    final isDark = theme.brightness == Brightness.dark;

    final surfaceColor = isDark ? AppColors.darkSurface : AppColors.surface;
    final bgColor = isDark ? AppColors.darkBgApp : AppColors.bgApp;
    final borderColor = isDark ? AppColors.darkBorder : AppColors.border;
    final textColor = isDark ? AppColors.darkTextPrimary : AppColors.textPrimary;
    final textSecColor = isDark ? AppColors.darkTextSecondary : AppColors.textSecondary;

    if (isDesktop) {
      return Scaffold(
        backgroundColor: bgColor,
        body: Row(
          children: [
            _buildEnterpriseSidebar(context, selectedIndex, fullName,
                roleDisplayName, initial, role, unreadCount, isDark),
            Expanded(
              child: Column(
                children: [
                  _buildDesktopHeader(
                      context, currentUser, fullName, roleDisplayName, initial, unreadCount, isDark),
                  Expanded(child: widget.child),
                ],
              ),
            ),
          ],
        ),
      );
    }

    String currentPath = '';
    try {
      currentPath = GoRouterState.of(context).uri.path;
    } catch (_) {}

    final showReports = role == 'ROLE_HR' || role == 'ROLE_ADMIN';

    // Map location to mobile bottom nav index
    int bottomNavIndex = 0;
    if (currentPath.startsWith('/tickets')) {
      bottomNavIndex = 1;
    } else if (currentPath.startsWith('/reports') && showReports) {
      bottomNavIndex = 2;
    } else if (currentPath.startsWith('/settings') || currentPath.startsWith('/users')) {
      bottomNavIndex = showReports ? 3 : 2;
    }

    return Scaffold(
      backgroundColor: bgColor,
      appBar: PreferredSize(
        preferredSize: const Size.fromHeight(56),
        child: Container(
          decoration: BoxDecoration(
            color: surfaceColor,
            border: Border(bottom: BorderSide(color: borderColor)),
          ),
          child: AppBar(
            elevation: 0,
            scrolledUnderElevation: 0,
            backgroundColor: surfaceColor,
            title: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.shield_outlined,
                      color: AppColors.primary, size: 20),
                ),
                const SizedBox(width: AppSpacing.sm),
                Text(
                  'Service Desk',
                  style: TextStyle(
                    color: textColor,
                    fontWeight: FontWeight.w800,
                    fontSize: 18,
                  ),
                ),
              ],
            ),
            actions: [
              IconButton(
                icon: _buildNotificationBadge(
                  icon: Icon(Icons.notifications_none_rounded, color: textSecColor),
                  unreadCount: unreadCount,
                ),
                tooltip: 'Notifications',
                onPressed: () => context.go('/notifications'),
              ),
              IconButton(
                icon: Icon(Icons.logout_rounded, color: textSecColor),
                tooltip: 'Log Out',
                onPressed: _confirmLogout,
              ),
            ],
          ),
        ),
      ),
      body: widget.child,
      bottomNavigationBar: Container(
        decoration: BoxDecoration(
          color: surfaceColor,
          border: Border(
            top: BorderSide(color: borderColor, width: 1),
          ),
          boxShadow: AppShadows.small,
        ),
        child: NavigationBarTheme(
          data: NavigationBarThemeData(
            indicatorColor: Colors.transparent, // Subtle, clean without awkward jumping pill
            backgroundColor: Colors.transparent,
            elevation: 0,
            height: 60,
            labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
            labelTextStyle: MaterialStateProperty.resolveWith((states) {
              if (states.contains(MaterialState.selected)) {
                return const TextStyle(
                  fontSize: 11.5,
                  fontWeight: FontWeight.w700,
                  color: AppColors.primary,
                  letterSpacing: 0.2,
                );
              }
              return TextStyle(
                fontSize: 11.5,
                fontWeight: FontWeight.w500,
                color: textSecColor,
              );
            }),
            iconTheme: MaterialStateProperty.resolveWith((states) {
              if (states.contains(MaterialState.selected)) {
                return const IconThemeData(
                  color: AppColors.primary,
                  size: 22,
                );
              }
              return IconThemeData(
                color: textSecColor,
                size: 22,
              );
            }),
          ),
          child: NavigationBar(
            selectedIndex: bottomNavIndex,
            backgroundColor: surfaceColor,
            elevation: 0,
            height: 60,
            onDestinationSelected: (int index) {
              if (index == 0) {
                context.go('/dashboard');
              } else if (index == 1) {
                context.go('/tickets');
              } else if (showReports && index == 2) {
                context.go('/reports');
              } else {
                context.go('/settings');
              }
            },
            destinations: [
              const NavigationDestination(
                icon: Icon(Icons.dashboard_outlined),
                selectedIcon: Icon(Icons.dashboard_rounded, color: AppColors.primary),
                label: 'Dashboard',
              ),
              const NavigationDestination(
                icon: Icon(Icons.confirmation_number_outlined),
                selectedIcon: Icon(Icons.confirmation_number_rounded, color: AppColors.primary),
                label: 'Tickets',
              ),
              if (showReports)
                const NavigationDestination(
                  icon: Icon(Icons.bar_chart_outlined),
                  selectedIcon: Icon(Icons.bar_chart_rounded, color: AppColors.primary),
                  label: 'Reports',
                ),
              const NavigationDestination(
                icon: Icon(Icons.settings_outlined),
                selectedIcon: Icon(Icons.settings_rounded, color: AppColors.primary),
                label: 'Settings',
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildDesktopHeader(BuildContext context, User? currentUser, String fullName,
      String roleDisplayName, String initial, int unreadCount, bool isDark) {
    final surfaceColor = isDark ? AppColors.darkSurface : AppColors.surface;
    final borderColor = isDark ? AppColors.darkBorder : AppColors.border;
    final textColor = isDark ? AppColors.darkTextPrimary : AppColors.textPrimary;
    final textSecColor = isDark ? AppColors.darkTextSecondary : AppColors.textSecondary;

    return Container(
      height: 60,
      padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
      decoration: BoxDecoration(
        color: surfaceColor,
        border: Border(bottom: BorderSide(color: borderColor)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Flexible(
            child: Text(
              'Enterprise Support & Operations',
              overflow: TextOverflow.ellipsis,
              style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w600,
                  color: textSecColor),
            ),
          ),
          Row(
            children: [
              IconButton(
                icon: _buildNotificationBadge(
                  icon: Icon(Icons.notifications_none_rounded, color: textSecColor),
                  unreadCount: unreadCount,
                ),
                tooltip: 'Notifications',
                onPressed: () => context.go('/notifications'),
              ),
              const SizedBox(width: AppSpacing.sm),
              InkWell(
                onTap: () => context.go('/settings'),
                borderRadius: BorderRadius.circular(AppRadius.avatar),
                child: Row(
                  children: [
                    AppAvatar(
                      profilePicture: currentUser?.profilePicture,
                      fullName: fullName,
                      radius: 16,
                    ),
                    const SizedBox(width: AppSpacing.sm),
                    Column(
                      mainAxisAlignment: MainAxisAlignment.center,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(fullName,
                            style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.bold,
                                color: textColor)),
                        Text(roleDisplayName,
                            style: TextStyle(
                                fontSize: 11, color: textSecColor)),
                      ],
                    ),
                  ],
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildEnterpriseSidebar(BuildContext context, int selectedIndex,
      String fullName, String roleDisplayName, String initial, String? role, int unreadCount, bool isDark) {
    final surfaceColor = isDark ? AppColors.darkSurface : AppColors.surface;
    final borderColor = isDark ? AppColors.darkBorder : AppColors.border;
    final textColor = isDark ? AppColors.darkTextPrimary : AppColors.textPrimary;

    return Container(
      width: 250,
      decoration: BoxDecoration(
        color: surfaceColor,
        border: Border(right: BorderSide(color: borderColor)),
      ),
      child: Column(
        children: [
          // Logo Header
          Container(
            height: 60,
            padding: const EdgeInsets.symmetric(horizontal: AppSpacing.lg),
            alignment: Alignment.centerLeft,
            decoration: BoxDecoration(
              border: Border(bottom: BorderSide(color: borderColor)),
            ),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(6),
                  decoration: BoxDecoration(
                    color: AppColors.primaryLight,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.shield_outlined,
                      color: AppColors.primary, size: 20),
                ),
                const SizedBox(width: AppSpacing.sm),
                Flexible(
                  child: Text(
                    'Service Desk',
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      color: textColor,
                      fontWeight: FontWeight.w800,
                      fontSize: 18,
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Menu Items
          Expanded(
            child: ListView(
              padding: const EdgeInsets.all(AppSpacing.md),
              children: [
                _buildSidebarNavItem(Icons.grid_view_rounded, 'Dashboard',
                    selectedIndex == 0, () => _onItemTapped(0), isDark),
                _buildSidebarNavItem(Icons.confirmation_number_outlined,
                    'Tickets', selectedIndex == 1, () => _onItemTapped(1), isDark),
                _buildSidebarNavItem(Icons.notifications_outlined,
                    'Notifications', selectedIndex == 2, () => _onItemTapped(2), isDark,
                    badgeCount: unreadCount),
                if (role == 'ROLE_HR' || role == 'ROLE_ADMIN')
                  _buildSidebarNavItem(Icons.analytics_outlined, 'Reports',
                      selectedIndex == 3, () => _onItemTapped(3), isDark),
                _buildSidebarNavItem(Icons.settings_outlined, 'Settings',
                    selectedIndex == 4, () => _onItemTapped(4), isDark),
                if (role == 'ROLE_ADMIN')
                  _buildSidebarNavItem(Icons.people_outline, 'Users',
                      selectedIndex == 5, () => _onItemTapped(5), isDark),
              ],
            ),
          ),

          // Footer User Action
          Container(
            padding: const EdgeInsets.all(AppSpacing.md),
            decoration: BoxDecoration(
              border: Border(top: BorderSide(color: borderColor)),
            ),
            child: AppButton(
              text: 'Log Out',
              variant: AppButtonVariant.outline,
              icon: Icons.logout,
              isFullWidth: true,
              onPressed: _confirmLogout,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSidebarNavItem(
      IconData icon, String label, bool isActive, VoidCallback onTap, bool isDark,
      {int badgeCount = 0}) {
    final textSecColor = isDark ? AppColors.darkTextSecondary : AppColors.textSecondary;

    return Container(
      margin: const EdgeInsets.only(bottom: AppSpacing.xs),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(
              horizontal: AppSpacing.md, vertical: 12),
          decoration: BoxDecoration(
            color: isActive ? AppColors.primaryLight : Colors.transparent,
            borderRadius: BorderRadius.circular(10),
          ),
          child: Row(
            children: [
              Icon(icon,
                  size: 20,
                  color: isActive ? AppColors.primary : textSecColor),
              const SizedBox(width: AppSpacing.md),
              Expanded(
                child: Text(
                  label,
                  style: TextStyle(
                    fontSize: 14,
                    fontWeight: isActive ? FontWeight.bold : FontWeight.w500,
                    color: isActive ? AppColors.primary : textSecColor,
                  ),
                ),
              ),
              if (badgeCount > 0)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEF4444),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: Text(
                    badgeCount > 99 ? '99+' : '$badgeCount',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 10,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}
