import 'package:flutter/material.dart';

/// Centralizes app-wide color and typography configuration.
class AppTheme {
  static const _primaryColor = Color(0xFF0047AB);
  static const _secondaryColor = Color(0xFF2F80ED);
  static const _backgroundColor = Color(0xFFF8FAFC);
  static const _textPrimary = Color(0xFF1E293B);
  static const _textSecondary = Color(0xFF64748B);
  static const _errorColor = Color(0xFFEF4444);

  static ThemeData get theme {
    final baseScheme = ColorScheme.fromSeed(seedColor: _primaryColor);
    final colorScheme = baseScheme.copyWith(
      primary: _primaryColor,
      secondary: _secondaryColor,
      surface: Colors.white,
      error: _errorColor,
      onPrimary: Colors.white,
      onSecondary: Colors.white,
      onSurface: _textPrimary,
      onError: Colors.white,
    );

    return ThemeData(
      colorScheme: colorScheme,
      scaffoldBackgroundColor: _backgroundColor,
      fontFamily: 'Roboto',
      useMaterial3: true,
      textTheme: const TextTheme(
        headlineSmall: TextStyle(
          fontWeight: FontWeight.w700,
          fontSize: 24,
          color: _primaryColor,
        ),
        titleMedium: TextStyle(
          fontWeight: FontWeight.w600,
          fontSize: 16,
          color: _textPrimary,
        ),
        bodyMedium: TextStyle(
          fontSize: 14,
          height: 1.5,
          color: _textSecondary,
        ),
        bodySmall: TextStyle(
          fontSize: 12,
          height: 1.5,
          color: _textSecondary,
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: Colors.white,
        contentPadding: const EdgeInsets.symmetric(
          horizontal: 16,
          vertical: 14,
        ),
        labelStyle: const TextStyle(
          color: _textSecondary,
          fontSize: 14,
        ),
        hintStyle: const TextStyle(
          color: _textSecondary,
          fontSize: 14,
        ),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(20),
          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(20),
          borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(20),
          borderSide: const BorderSide(color: _primaryColor, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(20),
          borderSide: const BorderSide(color: _errorColor),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(20),
          borderSide: const BorderSide(color: _errorColor, width: 1.5),
        ),
      ),
      filledButtonTheme: FilledButtonThemeData(
        style: FilledButton.styleFrom(
          backgroundColor: _primaryColor,
          foregroundColor: Colors.white,
          textStyle: const TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 16,
          ),
          minimumSize: const Size.fromHeight(52),
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: _secondaryColor,
          textStyle: const TextStyle(
            fontWeight: FontWeight.w600,
            fontSize: 14,
          ),
        ),
      ),
      dividerColor: const Color(0xFFE2E8F0),
    );
  }
}
