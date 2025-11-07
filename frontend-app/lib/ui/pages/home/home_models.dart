import 'package:flutter/material.dart';

@immutable
class HomeAction {
  const HomeAction({
    required this.icon,
    required this.title,
    required this.description,
    this.subActions = const [],
    this.permissionSlug,
  });

  final IconData icon;
  final String title;
  final String description;
  final List<HomeSubAction> subActions;
  final String? permissionSlug;

  HomeAction copyWith({
    IconData? icon,
    String? title,
    String? description,
    List<HomeSubAction>? subActions,
    String? permissionSlug,
  }) {
    return HomeAction(
      icon: icon ?? this.icon,
      title: title ?? this.title,
      description: description ?? this.description,
      subActions: subActions ?? this.subActions,
      permissionSlug: permissionSlug ?? this.permissionSlug,
    );
  }
}

@immutable
class HomeSubAction {
  const HomeSubAction({
    required this.icon,
    required this.label,
    this.permissionSlug,
  });

  final IconData icon;
  final String label;
  final String? permissionSlug;
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
