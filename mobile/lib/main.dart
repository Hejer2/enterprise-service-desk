import 'package:flutter/material.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import 'core/theme/app_theme.dart';
import 'core/widgets/main_scaffold.dart';
import 'providers/settings_provider.dart';
import 'screens/auth/login_screen.dart';
import 'screens/dashboard/dashboard_screen.dart';
import 'screens/tickets/ticket_list_screen.dart';
import 'screens/settings/settings_screen.dart';
import 'screens/notifications/notifications_screen.dart';
import 'screens/approvals/approvals_screen.dart';
import 'screens/analytics/executive_analytics_screen.dart';
import 'screens/users/user_list_screen.dart';
import 'screens/reports/report_screen.dart';

void main() {
  runApp(const ProviderScope(child: MyApp()));
}

final routerProvider = Provider<GoRouter>((ref) {
  final authState = ref.watch(authProvider);

  return GoRouter(
    initialLocation: authState ? '/dashboard' : '/login',
    redirect: (context, state) {
      final isLoggingIn = state.uri.path == '/login';
      if (!authState && !isLoggingIn) return '/login';
      if (authState && isLoggingIn) return '/dashboard';
      return null;
    },
    routes: [
      GoRoute(
        path: '/login',
        builder: (context, state) => const LoginScreen(),
      ),
      ShellRoute(
        builder: (context, state, child) {
          return MainScaffold(child: child);
        },
        routes: [
          GoRoute(
            path: '/dashboard',
            builder: (context, state) => const DashboardScreen(),
          ),
          GoRoute(
            path: '/tickets',
            builder: (context, state) => const TicketListScreen(),
          ),
          GoRoute(
            path: '/reports',
            builder: (context, state) => const ReportScreen(),
          ),
          GoRoute(
            path: '/settings',
            builder: (context, state) => const SettingsScreen(),
          ),
          GoRoute(
            path: '/notifications',
            builder: (context, state) => const NotificationsScreen(),
          ),
          GoRoute(
            path: '/approvals',
            builder: (context, state) => const ApprovalsScreen(),
          ),
          GoRoute(
            path: '/analytics',
            builder: (context, state) => const ExecutiveAnalyticsScreen(),
          ),
          GoRoute(
            path: '/users',
            builder: (context, state) => const UserListScreen(),
          ),
        ],
      ),
    ],
  );
});

class MyApp extends ConsumerWidget {
  const MyApp({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final router = ref.watch(routerProvider);
    final themeMode = ref.watch(themeModeProvider);
    final locale = ref.watch(appLocaleProvider);

    return MaterialApp.router(
      title: 'Service Desk Mobile',
      debugShowCheckedModeBanner: false,
      themeMode: themeMode,
      theme: AppTheme.lightTheme,
      darkTheme: AppTheme.darkTheme,
      locale: locale,
      localizationsDelegates: const [
        GlobalMaterialLocalizations.delegate,
        GlobalWidgetsLocalizations.delegate,
        GlobalCupertinoLocalizations.delegate,
      ],
      supportedLocales: const [
        Locale('en', 'US'),
        Locale('en'),
        Locale('ar', 'SA'),
        Locale('ar'),
      ],
      routerConfig: router,
    );
  }
}
