import 'package:cloud_firestore/cloud_firestore.dart';

class SubscriptionModel {
  final String userId;
  final String plan;
  final DateTime expiryDate;

  const SubscriptionModel({
    required this.userId,
    required this.plan,
    required this.expiryDate,
  });

  factory SubscriptionModel.fromMap(Map<String, dynamic> map, String userId) {
    return SubscriptionModel(
      userId: userId,
      plan: map['plan'] as String? ?? '',
      expiryDate:
          (map['expiryDate'] as Timestamp?)?.toDate() ?? DateTime.now(),
    );
  }

  Map<String, dynamic> toMap() {
    return {
      'userId': userId,
      'plan': plan,
      'expiryDate': Timestamp.fromDate(expiryDate),
    };
  }

  bool get isActive => expiryDate.isAfter(DateTime.now());
}
