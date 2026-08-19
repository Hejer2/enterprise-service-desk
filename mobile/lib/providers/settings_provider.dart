import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../repositories/settings_repository.dart';
import '../services/api_client.dart';

final settingsRepositoryProvider =
    Provider((ref) => SettingsRepository(ref.read(apiClientProvider)));

class ThemeModeNotifier extends StateNotifier<ThemeMode> {
  ThemeModeNotifier() : super(ThemeMode.light) {
    _loadTheme();
  }

  Future<void> _loadTheme() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final themeStr = prefs.getString('app_theme_mode');
      if (themeStr == 'dark') {
        state = ThemeMode.dark;
      } else if (themeStr == 'light') {
        state = ThemeMode.light;
      }
    } catch (_) {}
  }

  Future<void> setThemeMode(ThemeMode mode) async {
    state = mode;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString(
          'app_theme_mode', mode == ThemeMode.dark ? 'dark' : 'light');
    } catch (_) {}
  }
}

final themeModeProvider =
    StateNotifierProvider<ThemeModeNotifier, ThemeMode>((ref) {
  return ThemeModeNotifier();
});

class AppLocaleNotifier extends StateNotifier<Locale> {
  AppLocaleNotifier() : super(const Locale('en')) {
    _loadLocale();
  }

  Future<void> _loadLocale() async {
    try {
      final prefs = await SharedPreferences.getInstance();
      final lang = prefs.getString('app_language');
      if (lang == 'ar') {
        state = const Locale('ar');
      } else {
        state = const Locale('en');
      }
    } catch (_) {}
  }

  Future<void> setLocale(String languageCode) async {
    final newLocale = Locale(languageCode);
    state = newLocale;
    try {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setString('app_language', languageCode);
    } catch (_) {}
  }
}

final appLocaleProvider =
    StateNotifierProvider<AppLocaleNotifier, Locale>((ref) {
  return AppLocaleNotifier();
});
