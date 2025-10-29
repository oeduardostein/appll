import 'package:flutter/material.dart';

@immutable
class HomeAction {
  const HomeAction({
    required this.icon,
    required this.title,
    required this.description,
    this.subActions = const [],
  });

  final IconData icon;
  final String title;
  final String description;
  final List<HomeSubAction> subActions;
}

@immutable
class HomeSubAction {
  const HomeSubAction({
    required this.icon,
    required this.label,
  });

  final IconData icon;
  final String label;
}

@immutable
class RecentVehicle {
  const RecentVehicle({
    required this.plate,
    required this.summary,
    required this.dateTime,
  });

  final String plate;
  final String summary;
  final DateTime dateTime;
}
