class CannedResponse {
  final int id;
  final String title;
  final String content;
  final String? category;

  CannedResponse({
    required this.id,
    required this.title,
    required this.content,
    this.category,
  });

  factory CannedResponse.fromJson(Map<String, dynamic> json) {
    return CannedResponse(
      id: json['id'] is int ? json['id'] : int.parse(json['id'].toString()),
      title: json['title'] ?? '',
      content: json['content'] ?? '',
      category: json['category'],
    );
  }
}
