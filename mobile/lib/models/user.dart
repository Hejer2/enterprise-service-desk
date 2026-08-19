import 'role.dart';

class User {
  final int id;
  final String email;
  final String firstName;
  final String lastName;
  final String? phone;
  final String? profilePicture;
  final Role? roleEntity;
  final String language;
  final String theme;

  User({
    required this.id,
    required this.email,
    required this.firstName,
    required this.lastName,
    this.phone,
    this.profilePicture,
    this.roleEntity,
    this.language = 'en',
    this.theme = 'light',
  });

  String get fullName => '$firstName $lastName';

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] as int,
      email: json['email'] as String,
      firstName: json['firstName'] as String,
      lastName: json['lastName'] as String,
      phone: json['phone'] as String?,
      profilePicture: json['profilePicture'] as String?,
      roleEntity: json['roleEntity'] != null
          ? Role.fromJson(json['roleEntity'])
          : (json['role'] != null
              ? Role(
                  id: 0,
                  name: json['role'],
                  displayName: json['roleDisplayName'] ?? json['role'])
              : null),
      language: json['language'] as String? ?? 'en',
      theme: json['theme'] as String? ?? 'light',
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'email': email,
      'firstName': firstName,
      'lastName': lastName,
      'phone': phone,
      'profilePicture': profilePicture,
      'roleEntity': roleEntity?.toJson(),
      'language': language,
      'theme': theme,
    };
  }
}
