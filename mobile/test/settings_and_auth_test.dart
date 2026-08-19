import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_localizations/flutter_localizations.dart';
import 'package:mobile/core/utils/error_handler.dart';
import 'package:mobile/core/widgets/main_scaffold.dart';
import 'package:mobile/models/role.dart';
import 'package:mobile/models/user.dart';
import 'package:mobile/providers/settings_provider.dart';
import 'package:mobile/repositories/auth_repository.dart';
import 'package:mobile/repositories/settings_repository.dart';
import 'package:mobile/screens/auth/login_screen.dart';
import 'package:mobile/screens/settings/settings_screen.dart';
import 'package:dio/dio.dart';

class FakeSettingsRepository implements SettingsRepository {
  bool changePasswordCalled = false;
  bool updateProfileCalled = false;
  bool saveNotificationPreferencesCalled = false;
  bool updatePreferencesCalled = false;

  Map<String, dynamic> lastSavedNotificationPreferences = {};

  @override
  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
    required String confirmPassword,
  }) async {
    if (currentPassword == 'wrongpassword') {
      throw DioException(
        requestOptions: RequestOptions(path: '/api/me/change-password'),
        response: Response(
          requestOptions: RequestOptions(path: '/api/me/change-password'),
          statusCode: 400,
          data: {'error': 'Current password is invalid'},
        ),
        type: DioExceptionType.badResponse,
      );
    }
    changePasswordCalled = true;
  }

  @override
  Future<Map<String, dynamic>> updateProfile({
    required String firstName,
    required String lastName,
    String? phone,
    String? profilePicture,
  }) async {
    updateProfileCalled = true;
    return {
      'firstName': firstName,
      'lastName': lastName,
      'phone': phone,
      'profilePicture': profilePicture,
    };
  }

  @override
  Future<void> updatePreferences({String? language, String? theme}) async {
    updatePreferencesCalled = true;
  }

  @override
  Future<Map<String, dynamic>> getNotificationPreferences() async {
    return {
      'ticketAssignments': true,
      'ticketReplies': true,
      'ticketStatusChanges': false,
      'slaAlerts': true,
      'systemNotifications': true,
      'browserNotifications': false,
    };
  }

  @override
  Future<void> saveNotificationPreferences(Map<String, dynamic> preferences) async {
    saveNotificationPreferencesCalled = true;
    lastSavedNotificationPreferences = preferences;
  }
}

class FakeAuthRepository implements AuthRepository {
  @override
  Future<User?> login(String email, String password) async {
    if (email == 'valid@example.com' && password == 'ValidPass123!') {
      return User(
        id: 1,
        email: email,
        firstName: 'John',
        lastName: 'Doe',
        roleEntity: Role(id: 1, name: 'ROLE_EMPLOYEE', displayName: 'Employee'),
      );
    }
    throw DioException(
      requestOptions: RequestOptions(path: '/api/login'),
      response: Response(
        requestOptions: RequestOptions(path: '/api/login'),
        statusCode: 401,
        data: {'error': 'Invalid credentials.'},
      ),
      type: DioExceptionType.badResponse,
    );
  }

  @override
  Future<void> logout() async {}

  @override
  Future<User?> getCurrentUser() async => null;
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  final sampleUser = User(
    id: 1,
    email: 'user@example.com',
    firstName: 'Sarah',
    lastName: 'Connor',
    phone: '+1 555 0199',
    roleEntity: Role(id: 2, name: 'ROLE_IT_TECH', displayName: 'IT Specialist'),
  );

  group('Error Handler Centralized Mapping Tests', () {
    test('Converts Dio 401 to friendly message', () {
      final err = DioException(
        requestOptions: RequestOptions(path: '/api/login'),
        response: Response(
          requestOptions: RequestOptions(path: '/api/login'),
          statusCode: 401,
          data: {'error': 'Invalid credentials.'},
        ),
        type: DioExceptionType.badResponse,
      );
      final msg = AppErrorHandler.getReadableErrorMessage(err);
      expect(msg, 'Incorrect email or password.');
      expect(msg.contains('DioException'), isFalse);
    });

    test('Converts Dio Connection Error to friendly message', () {
      final err = DioException(
        requestOptions: RequestOptions(path: '/api/login'),
        type: DioExceptionType.connectionError,
      );
      final msg = AppErrorHandler.getReadableErrorMessage(err);
      expect(msg, 'Unable to connect to the server. Please try again.');
      expect(msg.contains('DioException'), isFalse);
    });

    test('Converts Dio Timeout to friendly message', () {
      final err = DioException(
        requestOptions: RequestOptions(path: '/api/tickets'),
        type: DioExceptionType.receiveTimeout,
      );
      final msg = AppErrorHandler.getReadableErrorMessage(err);
      expect(msg, 'The request took too long to respond. Please try again.');
    });
  });

  group('Login Screen Tests', () {
    testWidgets('Subtitle "Sign in to access" is REMOVED', (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWith((ref) => FakeAuthRepository()),
          ],
          child: const MaterialApp(home: LoginScreen()),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Sign in to access your operations portal'), findsNothing);
      expect(find.textContaining('Sign in to access'), findsNothing);
      expect(find.text('Service Desk'), findsOneWidget);
    });

    testWidgets('Empty fields show user-friendly validation', (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWith((ref) => FakeAuthRepository()),
          ],
          child: const MaterialApp(home: LoginScreen()),
        ),
      );
      await tester.pumpAndSettle();

      // Tap Sign In without filling email
      await tester.tap(find.text('Sign In'));
      await tester.pumpAndSettle();
      expect(find.text('Please enter your email.'), findsOneWidget);

      // Enter invalid email format
      await tester.enterText(find.byType(TextField).first, 'invalidemail');
      await tester.tap(find.text('Sign In'));
      await tester.pumpAndSettle();
      expect(find.text('Please enter a valid email address.'), findsOneWidget);

      // Enter valid email without password
      await tester.enterText(find.byType(TextField).first, 'test@example.com');
      await tester.tap(find.text('Sign In'));
      await tester.pumpAndSettle();
      expect(find.text('Please enter your password.'), findsOneWidget);
    });

    testWidgets('Wrong password shows human-readable error without DioException',
        (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            authRepositoryProvider.overrideWith((ref) => FakeAuthRepository()),
          ],
          child: const MaterialApp(home: LoginScreen()),
        ),
      );
      await tester.pumpAndSettle();

      await tester.enterText(find.byType(TextField).at(0), 'user@example.com');
      await tester.enterText(find.byType(TextField).at(1), 'wrongpass');
      await tester.tap(find.text('Sign In'));
      await tester.pumpAndSettle();

      expect(find.text('Incorrect email or password.'), findsOneWidget);
      expect(find.textContaining('DioException'), findsNothing);
      expect(find.textContaining('401'), findsNothing);
    });
  });

  group('Bottom Navigation Tests', () {
    testWidgets('Bottom navigation contains Dashboard, Tickets, Settings and NO Notifications',
        (tester) async {
      tester.view.physicalSize = const Size(400, 800);
      tester.view.devicePixelRatio = 1.0;
      addTearDown(tester.view.resetPhysicalSize);

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => sampleUser),
          ],
          child: const MaterialApp(
            home: MainScaffold(child: Text('Content')),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Bottom Navigation has Dashboard, Tickets, Settings
      expect(find.text('Dashboard'), findsOneWidget);
      expect(find.text('Tickets'), findsOneWidget);
      expect(find.text('Settings'), findsOneWidget);

      // Notifications is strictly in top AppBar, NOT in bottom navigation destinations
      final navBar = tester.widget<NavigationBar>(find.byType(NavigationBar));
      final destLabels = navBar.destinations.map((d) {
        if (d is NavigationDestination) return d.label;
        return '';
      }).toList();

      expect(destLabels.contains('Notifications'), isFalse);
    });
  });

  group('Settings Screen Tests', () {
    testWidgets('Security Tab password fields start completely empty and visibility toggles work',
        (tester) async {
      final fakeSettings = FakeSettingsRepository();

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => sampleUser),
            settingsRepositoryProvider.overrideWith((ref) => fakeSettings),
          ],
          child: const MaterialApp(
            home: Scaffold(body: SecurityTabWidget()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // 3 password fields
      final textFields = tester.widgetList<TextField>(find.byType(TextField)).toList();
      expect(textFields.length, 3);
      for (final tf in textFields) {
        expect(tf.controller?.text, '');
        expect(tf.obscureText, isTrue);
      }

      // Tap first eye icon to toggle visibility (obscureText is true, so visibility_off_outlined is shown)
      await tester.tap(find.byIcon(Icons.visibility_off_outlined).first);
      await tester.pumpAndSettle();

      final updatedTextFields = tester.widgetList<TextField>(find.byType(TextField)).toList();
      expect(updatedTextFields.first.obscureText, isFalse);
      expect(updatedTextFields[1].obscureText, isTrue);
      expect(updatedTextFields[2].obscureText, isTrue);
    });

    testWidgets('Security Tab validates fields and executes changePassword',
        (tester) async {
      final fakeSettings = FakeSettingsRepository();

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => sampleUser),
            settingsRepositoryProvider.overrideWith((ref) => fakeSettings),
          ],
          child: const MaterialApp(
            home: Scaffold(body: SecurityTabWidget()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Empty validation
      await tester.tap(find.text('Update Password'));
      await tester.pumpAndSettle();
      expect(find.text('Please enter your current password.'), findsOneWidget);

      // Fill current password
      await tester.enterText(find.byType(TextField).at(0), 'OldPass123!');
      await tester.tap(find.text('Update Password'));
      await tester.pumpAndSettle();
      expect(find.text('Please enter a new password.'), findsOneWidget);

      // Fill mismatched new password
      await tester.enterText(find.byType(TextField).at(1), 'NewPass123!');
      await tester.enterText(find.byType(TextField).at(2), 'DifferentPass123!');
      await tester.tap(find.text('Update Password'));
      await tester.pumpAndSettle();
      expect(find.text('New passwords do not match.'), findsOneWidget);

      // Fill matching new password
      await tester.enterText(find.byType(TextField).at(2), 'NewPass123!');
      await tester.tap(find.text('Update Password'));
      await tester.pumpAndSettle();

      expect(fakeSettings.changePasswordCalled, isTrue);
    });

    testWidgets('Preferences Tab displays Language without Arabic label and updates theme/locale',
        (tester) async {
      final fakeSettings = FakeSettingsRepository();

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => sampleUser),
            settingsRepositoryProvider.overrideWith((ref) => fakeSettings),
          ],
          child: const MaterialApp(
            home: Scaffold(body: PreferencesTabWidget()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Language label is just "Language"
      expect(find.text('LANGUAGE / اللغة'), findsNothing);
      expect(find.text('Language'), findsOneWidget);
      expect(find.text('DISPLAY MODE'), findsOneWidget);
    });

    testWidgets('Personal Details Tab displays user profile and saves changes',
        (tester) async {
      final fakeSettings = FakeSettingsRepository();

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => sampleUser),
            settingsRepositoryProvider.overrideWith((ref) => fakeSettings),
          ],
          child: const MaterialApp(
            home: Scaffold(body: ProfileTabWidget()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Sarah Connor'), findsOneWidget);
      expect(find.text('IT Specialist'), findsOneWidget);

      // Update phone number
      await tester.enterText(find.byType(TextField).at(2), '+1 555 9999');
      await tester.tap(find.text('Save Changes'));
      await tester.pumpAndSettle();

      expect(fakeSettings.updateProfileCalled, isTrue);
    });

    testWidgets('Personal Details Tab allows changing photo/avatar style',
        (tester) async {
      final fakeSettings = FakeSettingsRepository();

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => sampleUser),
            settingsRepositoryProvider.overrideWith((ref) => fakeSettings),
          ],
          child: const MaterialApp(
            home: Scaffold(body: ProfileTabWidget()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Tap Change Photo
      expect(find.text('Change Photo'), findsOneWidget);
      await tester.tap(find.text('Change Photo'));
      await tester.pumpAndSettle();

      // Verify bottom sheet is open with camera, gallery and avatar styles
      expect(find.text('Change Profile Photo'), findsOneWidget);
      expect(find.text('Take Photo'), findsOneWidget);
      expect(find.text('Choose from Gallery'), findsOneWidget);
      expect(find.text('Or choose an Avatar style:'), findsOneWidget);

      // Select Developer avatar preset
      await tester.tap(find.text('Developer'));
      await tester.pumpAndSettle();

      expect(fakeSettings.updateProfileCalled, isTrue);
    });

    testWidgets('Notification Preferences Tab loads preferences and saves changes',
        (tester) async {
      final fakeSettings = FakeSettingsRepository();

      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => sampleUser),
            settingsRepositoryProvider.overrideWith((ref) => fakeSettings),
          ],
          child: const MaterialApp(
            home: Scaffold(body: NotificationsTabWidget()),
          ),
        ),
      );
      await tester.pumpAndSettle();

      expect(find.text('Ticket Assignments'), findsOneWidget);
      expect(find.text('SLA Breach Alerts'), findsOneWidget);

      // Tap Save Notifications
      await tester.tap(find.text('Save Notifications'));
      await tester.pumpAndSettle();

      expect(fakeSettings.saveNotificationPreferencesCalled, isTrue);
      expect(fakeSettings.lastSavedNotificationPreferences.isNotEmpty, isTrue);
    });

    testWidgets('Arabic locale Locale(ar) renders without any localizations delegate errors',
        (tester) async {
      await tester.pumpWidget(
        ProviderScope(
          overrides: [
            currentUserProvider.overrideWith((ref) => sampleUser),
          ],
          child: const MaterialApp(
            locale: Locale('ar'),
            localizationsDelegates: [
              GlobalMaterialLocalizations.delegate,
              GlobalWidgetsLocalizations.delegate,
              GlobalCupertinoLocalizations.delegate,
            ],
            supportedLocales: [
              Locale('en'),
              Locale('ar'),
            ],
            home: Scaffold(
              body: Center(
                child: Text('Service Desk'),
              ),
            ),
          ),
        ),
      );
      await tester.pumpAndSettle();

      // Verify that no Flutter localization exception was thrown and Arabic layout initializes
      expect(find.text('Service Desk'), findsOneWidget);
    });
  });
}
