import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:google_fonts/google_fonts.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:mobile/main.dart';
import 'package:mobile/models/user.dart';
import 'package:mobile/screens/auth/login_screen.dart';
import 'package:mobile/repositories/auth_repository.dart';

class MockAuthRepository implements AuthRepository {
  @override
  Future<User?> login(String email, String password) async => null;

  @override
  Future<void> logout() async {}

  @override
  Future<User?> getCurrentUser() async => null;
}

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUpAll(() {
    GoogleFonts.config.allowRuntimeFetching = false;
    SharedPreferences.setMockInitialValues({});
  });

  testWidgets('App initialization test', (WidgetTester tester) async {
    await tester.pumpWidget(
      ProviderScope(
        overrides: [
          authRepositoryProvider.overrideWith((ref) => MockAuthRepository()),
        ],
        child: const MyApp(),
      ),
    );
    await tester.pump();
    expect(find.byType(MyApp), findsOneWidget);
  });
}
