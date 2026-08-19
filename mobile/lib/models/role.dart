class Role {
  final int id;
  final String name;
  final String displayName;

  Role({
    required this.id,
    required this.name,
    required this.displayName,
  });

  factory Role.fromJson(Map<String, dynamic> json) {
    return Role(
      id: json['id'] as int,
      name: json['name'] as String,
      displayName: json['displayName'] ?? json['name'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'displayName': displayName,
    };
  }
}
