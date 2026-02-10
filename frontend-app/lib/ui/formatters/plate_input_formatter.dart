import 'package:flutter/services.dart';

enum PlateFormat { antiga, mercosul }

class PlateUtils {
  static final RegExp _oldPlatePattern = RegExp(r'^[A-Z]{3}-[0-9]{4}$');
  static final RegExp _mercosulPlatePattern = RegExp(
    r'^[A-Z]{3}-[0-9][A-Z0-9][0-9]{2}$',
  );

  static String sanitize(String value) {
    return value.replaceAll(RegExp('[^A-Za-z0-9]'), '').toUpperCase();
  }

  static String format(String value, PlateFormat format) {
    final cleaned = sanitize(value);

    if (format == PlateFormat.antiga) {
      var letters = '';
      var digits = '';
      for (final char in cleaned.split('')) {
        if (letters.length < 3) {
          if (RegExp('[A-Z]').hasMatch(char)) {
            letters += char;
          }
          continue;
        }
        if (digits.length < 4 && RegExp('[0-9]').hasMatch(char)) {
          digits += char;
        }
      }
      return letters.length == 3 ? '$letters-$digits' : letters;
    }

    var letters = '';
    var digit = '';
    var middle = '';
    var lastDigits = '';
    for (final char in cleaned.split('')) {
      if (letters.length < 3) {
        if (RegExp('[A-Z]').hasMatch(char)) {
          letters += char;
        }
        continue;
      }
      if (digit.isEmpty) {
        if (RegExp('[0-9]').hasMatch(char)) {
          digit = char;
        }
        continue;
      }
      if (middle.isEmpty) {
        if (RegExp('[A-Z0-9]').hasMatch(char)) {
          middle = char;
        }
        continue;
      }
      if (lastDigits.length < 2 && RegExp('[0-9]').hasMatch(char)) {
        lastDigits += char;
      }
    }

    return letters.length == 3 ? '$letters-$digit$middle$lastDigits' : letters;
  }

  static bool isValid(String value, PlateFormat format) {
    final plate = value.trim().toUpperCase();
    return switch (format) {
      PlateFormat.antiga => _oldPlatePattern.hasMatch(plate),
      PlateFormat.mercosul => _mercosulPlatePattern.hasMatch(plate),
    };
  }

  static PlateFormat? inferFormat(String value) {
    final cleaned = sanitize(value);
    if (RegExp(r'^[A-Z]{3}[0-9]{4}$').hasMatch(cleaned)) {
      return PlateFormat.antiga;
    }
    if (RegExp(r'^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$').hasMatch(cleaned)) {
      return PlateFormat.mercosul;
    }
    return null;
  }
}

class PlateInputFormatter extends TextInputFormatter {
  PlateInputFormatter(this.format);

  final PlateFormat format;

  @override
  TextEditingValue formatEditUpdate(
    TextEditingValue oldValue,
    TextEditingValue newValue,
  ) {
    final formatted = PlateUtils.format(newValue.text, format);
    return TextEditingValue(
      text: formatted,
      selection: TextSelection.collapsed(offset: formatted.length),
    );
  }
}

