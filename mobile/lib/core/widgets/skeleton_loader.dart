import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class SkeletonLoader extends StatefulWidget {
  final double width;
  final double height;
  final double borderRadius;

  const SkeletonLoader({
    super.key,
    required this.width,
    required this.height,
    this.borderRadius = 8.0,
  });

  @override
  State<SkeletonLoader> createState() => _SkeletonLoaderState();
}

class _SkeletonLoaderState extends State<SkeletonLoader>
    with SingleTickerProviderStateMixin {
  late AnimationController _controller;
  late Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat();
    _animation = Tween<double>(begin: -1.0, end: 2.0).animate(
      CurvedAnimation(parent: _controller, curve: Curves.easeInOutSine),
    );
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return AnimatedBuilder(
      animation: _animation,
      builder: (context, child) {
        return Container(
          width: widget.width,
          height: widget.height,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(widget.borderRadius),
            gradient: LinearGradient(
              begin: Alignment(_animation.value - 1.0, 0),
              end: Alignment(_animation.value, 0),
              colors: const [
                Color(0xFFE2E8F0),
                Color(0xFFF1F5F9),
                Color(0xFFE2E8F0),
              ],
            ),
          ),
        );
      },
    );
  }
}

class DashboardSkeletonLoader extends StatelessWidget {
  const DashboardSkeletonLoader({super.key});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        const Row(
          children: [
            Expanded(child: SkeletonLoader(width: double.infinity, height: 90, borderRadius: 12)),
            SizedBox(width: 12),
            Expanded(child: SkeletonLoader(width: double.infinity, height: 90, borderRadius: 12)),
            SizedBox(width: 12),
            Expanded(child: SkeletonLoader(width: double.infinity, height: 90, borderRadius: 12)),
          ],
        ),
        const SizedBox(height: 24),
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.border),
          ),
          child: const Column(
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  SkeletonLoader(width: 140, height: 20, borderRadius: 6),
                  SkeletonLoader(width: 80, height: 20, borderRadius: 6),
                ],
              ),
              SizedBox(height: 16),
              SkeletonLoader(width: double.infinity, height: 50, borderRadius: 8),
              SizedBox(height: 8),
              SkeletonLoader(width: double.infinity, height: 50, borderRadius: 8),
              SizedBox(height: 8),
              SkeletonLoader(width: double.infinity, height: 50, borderRadius: 8),
            ],
          ),
        ),
      ],
    );
  }
}

class TicketListSkeletonLoader extends StatelessWidget {
  final int count;
  const TicketListSkeletonLoader({super.key, this.count = 4});

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    return ListView.separated(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      padding: const EdgeInsets.symmetric(vertical: 8),
      itemCount: count,
      separatorBuilder: (_, __) => const SizedBox(height: 12),
      itemBuilder: (_, __) => Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: isDark ? AppColors.darkSurface : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
            color: isDark ? AppColors.darkBorder : AppColors.border,
          ),
        ),
        child: const Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                SkeletonLoader(width: 90, height: 18, borderRadius: 6),
                SkeletonLoader(width: 70, height: 22, borderRadius: 12),
              ],
            ),
            SizedBox(height: 12),
            SkeletonLoader(width: 220, height: 16, borderRadius: 4),
            SizedBox(height: 8),
            SkeletonLoader(width: double.infinity, height: 14, borderRadius: 4),
            SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                SkeletonLoader(width: 100, height: 14, borderRadius: 4),
                SkeletonLoader(width: 80, height: 14, borderRadius: 4),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class TicketDetailSkeletonLoader extends StatelessWidget {
  const TicketDetailSkeletonLoader({super.key});

  @override
  Widget build(BuildContext context) {
    return const SingleChildScrollView(
      padding: EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SkeletonLoader(width: 120, height: 24, borderRadius: 6),
          SizedBox(height: 12),
          SkeletonLoader(width: 260, height: 20, borderRadius: 6),
          SizedBox(height: 24),
          SkeletonLoader(width: double.infinity, height: 120, borderRadius: 12),
          SizedBox(height: 24),
          SkeletonLoader(width: 140, height: 20, borderRadius: 6),
          SizedBox(height: 12),
          SkeletonLoader(width: double.infinity, height: 80, borderRadius: 12),
          SizedBox(height: 12),
          SkeletonLoader(width: double.infinity, height: 80, borderRadius: 12),
        ],
      ),
    );
  }
}

class ReportSkeletonLoader extends StatelessWidget {
  const ReportSkeletonLoader({super.key});

  @override
  Widget build(BuildContext context) {
    return const SingleChildScrollView(
      padding: EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(child: SkeletonLoader(width: double.infinity, height: 80, borderRadius: 12)),
              SizedBox(width: 12),
              Expanded(child: SkeletonLoader(width: double.infinity, height: 80, borderRadius: 12)),
            ],
          ),
          SizedBox(height: 24),
          SkeletonLoader(width: double.infinity, height: 220, borderRadius: 12),
        ],
      ),
    );
  }
}
