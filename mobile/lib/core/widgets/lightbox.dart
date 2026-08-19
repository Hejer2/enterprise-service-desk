import 'package:flutter/material.dart';

void openImageLightbox(
  BuildContext context, {
  required String imageUrl,
  required String title,
}) {
  Navigator.of(context).push(
    PageRouteBuilder(
      opaque: false,
      barrierDismissible: true,
      barrierColor: Colors.black87,
      pageBuilder: (BuildContext context, _, __) {
        return ImageLightboxOverlay(imageUrl: imageUrl, title: title);
      },
    ),
  );
}

class ImageLightboxOverlay extends StatelessWidget {
  final String imageUrl;
  final String title;

  const ImageLightboxOverlay({
    super.key,
    required this.imageUrl,
    required this.title,
  });

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: Colors.transparent,
      body: SafeArea(
        child: Stack(
          children: [
            // Interactive Pinch & Zoom Image View
            Center(
              child: InteractiveViewer(
                minScale: 0.5,
                maxScale: 4.0,
                child: Image.network(
                  imageUrl,
                  fit: BoxFit.contain,
                  errorBuilder: (context, error, stackTrace) => const Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(Icons.broken_image_rounded,
                          size: 64, color: Colors.white70),
                      SizedBox(height: 12),
                      Text(
                        'Unable to load image',
                        style: TextStyle(color: Colors.white70),
                      ),
                    ],
                  ),
                ),
              ),
            ),

            // Top Control Bar
            Positioned(
              top: 10,
              left: 16,
              right: 16,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Expanded(
                    child: Text(
                      title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close_rounded, color: Colors.white, size: 28),
                    tooltip: 'Close',
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
