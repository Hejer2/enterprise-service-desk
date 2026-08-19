import 'package:flutter/material.dart';
import 'package:google_fonts/google_fonts.dart';

class AppColors {
  // Primary (Slate Neutral & Professional Blue)
  static const Color primary = Color(0xFF2563EB);
  static const Color primaryHover = Color(0xFF1D4ED8);
  static const Color primaryLight = Color(0xFFEFF6FF);

  // Background & Surface
  static const Color bgApp = Color(0xFFF8FAFC);
  static const Color surface = Color(0xFFFFFFFF);

  // Borders
  static const Color border = Color(0xFFE2E8F0);
  static const Color borderStrong = Color(0xFFCBD5E1);

  // Text
  static const Color textPrimary = Color(0xFF0F172A);
  static const Color textSecondary = Color(0xFF64748B);

  // Statuses
  static const Color success = Color(0xFF10B981);
  static const Color successLight = Color(0xFFECFDF5);
  static const Color warning = Color(0xFFF59E0B);
  static const Color warningLight = Color(0xFFFEF3C7);
  static const Color danger = Color(0xFFEF4444);
  static const Color dangerLight = Color(0xFFFEF2F2);
  static const Color info = Color(0xFF3B82F6);
  static const Color infoLight = Color(0xFFEFF6FF);

  // Dark Theme Tokens
  static const Color darkBgApp = Color(0xFF0F172A);
  static const Color darkSurface = Color(0xFF1E293B);
  static const Color darkBorder = Color(0xFF334155);
  static const Color darkTextPrimary = Color(0xFFF8FAFC);
  static const Color darkTextSecondary = Color(0xFF94A3B8);
}

class AppSpacing {
  static const double xs = 4.0;
  static const double sm = 8.0;
  static const double md = 16.0;
  static const double lg = 24.0;
  static const double xl = 32.0;
  static const double xxl = 48.0;
}

class AppRadius {
  static const double button = 10.0;
  static const double input = 10.0;
  static const double card = 14.0;
  static const double modal = 20.0;
  static const double badge = 6.0;
  static const double avatar = 9999.0;
}

class AppShadows {
  static const List<BoxShadow> small = [
    BoxShadow(
      color: Color(0x0D0F172A),
      blurRadius: 2,
      offset: Offset(0, 1),
    ),
  ];

  static const List<BoxShadow> medium = [
    BoxShadow(
      color: Color(0x140F172A),
      blurRadius: 12,
      offset: Offset(0, 4),
    ),
  ];

  static const List<BoxShadow> large = [
    BoxShadow(
      color: Color(0x260F172A),
      blurRadius: 25,
      offset: Offset(0, 20),
    ),
  ];
}

class AppTheme {
  static ThemeData get lightTheme {
    return ThemeData(
      brightness: Brightness.light,
      primaryColor: AppColors.primary,
      scaffoldBackgroundColor: AppColors.bgApp,
      colorScheme: const ColorScheme.light(
        primary: AppColors.primary,
        secondary: AppColors.primary,
        surface: AppColors.surface,
        background: AppColors.bgApp,
        error: AppColors.danger,
        onPrimary: Colors.white,
        onSurface: AppColors.textPrimary,
      ),
      textTheme: TextTheme(
        displayLarge: GoogleFonts.plusJakartaSans(
            fontSize: 28,
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary),
        displayMedium: GoogleFonts.plusJakartaSans(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: AppColors.textPrimary),
        titleLarge: GoogleFonts.plusJakartaSans(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary),
        titleMedium: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: AppColors.textPrimary),
        bodyLarge: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.normal,
            color: AppColors.textPrimary),
        bodyMedium: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.normal,
            color: AppColors.textSecondary),
        labelLarge: GoogleFonts.plusJakartaSans(
            fontSize: 14, fontWeight: FontWeight.w600, color: Colors.white),
        bodySmall: GoogleFonts.plusJakartaSans(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: AppColors.textSecondary),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.surface,
        elevation: 0,
        scrolledUnderElevation: 1,
        iconTheme: IconThemeData(color: AppColors.textPrimary),
        titleTextStyle: TextStyle(
            color: AppColors.textPrimary,
            fontSize: 18,
            fontWeight: FontWeight.bold),
      ),
    );
  }

  static ThemeData get darkTheme {
    return ThemeData(
      brightness: Brightness.dark,
      primaryColor: AppColors.primary,
      scaffoldBackgroundColor: AppColors.darkBgApp,
      colorScheme: const ColorScheme.dark(
        primary: AppColors.primary,
        secondary: AppColors.primary,
        surface: AppColors.darkSurface,
        background: AppColors.darkBgApp,
        error: AppColors.danger,
        onPrimary: Colors.white,
        onSurface: AppColors.darkTextPrimary,
      ),
      textTheme: TextTheme(
        displayLarge: GoogleFonts.plusJakartaSans(
            fontSize: 28,
            fontWeight: FontWeight.bold,
            color: AppColors.darkTextPrimary),
        displayMedium: GoogleFonts.plusJakartaSans(
            fontSize: 22,
            fontWeight: FontWeight.bold,
            color: AppColors.darkTextPrimary),
        titleLarge: GoogleFonts.plusJakartaSans(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: AppColors.darkTextPrimary),
        titleMedium: GoogleFonts.plusJakartaSans(
            fontSize: 16,
            fontWeight: FontWeight.w600,
            color: AppColors.darkTextPrimary),
        bodyLarge: GoogleFonts.plusJakartaSans(
            fontSize: 15,
            fontWeight: FontWeight.normal,
            color: AppColors.darkTextPrimary),
        bodyMedium: GoogleFonts.plusJakartaSans(
            fontSize: 14,
            fontWeight: FontWeight.normal,
            color: AppColors.darkTextSecondary),
        labelLarge: GoogleFonts.plusJakartaSans(
            fontSize: 14, fontWeight: FontWeight.w600, color: Colors.white),
        bodySmall: GoogleFonts.plusJakartaSans(
            fontSize: 12,
            fontWeight: FontWeight.w500,
            color: AppColors.darkTextSecondary),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.darkSurface,
        elevation: 0,
        scrolledUnderElevation: 1,
        iconTheme: IconThemeData(color: AppColors.darkTextPrimary),
        titleTextStyle: TextStyle(
            color: AppColors.darkTextPrimary,
            fontSize: 18,
            fontWeight: FontWeight.bold),
      ),
    );
  }
}
