enum UserRole { user, uploader, admin }

class UserModel {
  final String id;
  final String name;
  final String email;
  final UserRole role;
  final bool subscriptionStatus;

  const UserModel({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.subscriptionStatus,
  });

  factory UserModel.fromMap(Map<String, dynamic> map, String id) {
    return UserModel(
      id: id,
      name: map['name'] as String? ?? '',
      email: map['email'] as String? ?? '',
      role: _parseRole(map['role'] as String? ?? 'user'),
      subscriptionStatus: map['subscriptionStatus'] as bool? ?? false,
    );
  }

  Map<String, dynamic> toMap() {
    return {
      'name': name,
      'email': email,
      'role': role.name,
      'subscriptionStatus': subscriptionStatus,
    };
  }

  UserModel copyWith({
    String? id,
    String? name,
    String? email,
    UserRole? role,
    bool? subscriptionStatus,
  }) {
    return UserModel(
      id: id ?? this.id,
      name: name ?? this.name,
      email: email ?? this.email,
      role: role ?? this.role,
      subscriptionStatus: subscriptionStatus ?? this.subscriptionStatus,
    );
  }

  static UserRole _parseRole(String role) {
    switch (role) {
      case 'admin':
        return UserRole.admin;
      case 'uploader':
        return UserRole.uploader;
      default:
        return UserRole.user;
    }
  }
}
