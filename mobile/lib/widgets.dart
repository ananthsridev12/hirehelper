import 'package:flutter/material.dart';
import 'package:flutter_svg/flutter_svg.dart';
import 'config.dart';

const Color primaryColor = Color(0xFF4F46E5);
const Color accentColor = Color(0xFF7C3AED);

String formatMoney(num amount) => '₹${amount.toStringAsFixed(2)}';

final Map<String, Color> _statusColors = {
  'pending': const Color(0xFFD97706),
  'offered': const Color(0xFFD97706),
  'assigned': const Color(0xFF4F46E5),
  'in_progress': const Color(0xFF4F46E5),
  'completed': const Color(0xFF16A34A),
  'cancelled': const Color(0xFFDC2626),
};

String statusLabel(String status) =>
    status.split('_').map((w) => w[0].toUpperCase() + w.substring(1)).join(' ');

class StatusBadge extends StatelessWidget {
  final String status;
  const StatusBadge({super.key, required this.status});

  @override
  Widget build(BuildContext context) {
    final color = _statusColors[status] ?? Colors.grey;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.12),
        borderRadius: BorderRadius.circular(999),
      ),
      child: Text(
        statusLabel(status),
        style: TextStyle(color: color, fontWeight: FontWeight.w600, fontSize: 12),
      ),
    );
  }
}

/// Generic "loading / error / data" wrapper so every screen doesn't
/// repeat the same FutureBuilder boilerplate.
class AsyncScreen<T> extends StatefulWidget {
  final Future<T> Function() load;
  final Widget Function(BuildContext context, T data, VoidCallback reload) builder;
  const AsyncScreen({super.key, required this.load, required this.builder});

  @override
  State<AsyncScreen<T>> createState() => _AsyncScreenState<T>();
}

class _AsyncScreenState<T> extends State<AsyncScreen<T>> {
  late Future<T> _future;

  @override
  void initState() {
    super.initState();
    _future = widget.load();
  }

  void _reload() {
    setState(() {
      _future = widget.load();
    });
  }

  @override
  Widget build(BuildContext context) {
    return FutureBuilder<T>(
      future: _future,
      builder: (context, snapshot) {
        if (snapshot.connectionState != ConnectionState.done) {
          return const Center(child: CircularProgressIndicator());
        }
        if (snapshot.hasError) {
          return Center(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text('${snapshot.error}', textAlign: TextAlign.center),
                  const SizedBox(height: 12),
                  ElevatedButton(onPressed: _reload, child: const Text('Retry')),
                ],
              ),
            ),
          );
        }
        return widget.builder(context, snapshot.data as T, _reload);
      },
    );
  }
}

void showError(BuildContext context, Object error) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text('$error'), backgroundColor: Colors.red.shade600),
  );
}

void showSuccess(BuildContext context, String message) {
  ScaffoldMessenger.of(context).showSnackBar(
    SnackBar(content: Text(message), backgroundColor: Colors.green.shade600),
  );
}

/// Renders an image returned by the API (a relative asset path like
/// "categories/plumber.svg" or "uploads/providers/xxx.png") as either an
/// SVG or a raster network image, falling back to [fallback] when there's
/// no path or the image fails to load.
class RemoteThumb extends StatelessWidget {
  final String? path;
  final Widget fallback;
  final BoxFit fit;
  const RemoteThumb({super.key, required this.path, required this.fallback, this.fit = BoxFit.cover});

  @override
  Widget build(BuildContext context) {
    final p = path;
    if (p == null || p.isEmpty) return fallback;
    final url = assetUrl(p);
    if (p.toLowerCase().endsWith('.svg')) {
      return SvgPicture.network(
        url,
        fit: fit,
        placeholderBuilder: (_) => fallback,
      );
    }
    return Image.network(
      url,
      fit: fit,
      errorBuilder: (_, __, ___) => fallback,
      loadingBuilder: (context, child, progress) =>
          progress == null ? child : Center(child: CircularProgressIndicator(strokeWidth: 2, value: progress.expectedTotalBytes != null ? progress.cumulativeBytesLoaded / progress.expectedTotalBytes! : null)),
    );
  }
}

/// A round profile photo with an initials/icon fallback, used for provider
/// photos across the app.
class AvatarThumb extends StatelessWidget {
  final String? path;
  final double radius;
  const AvatarThumb({super.key, required this.path, this.radius = 24});

  @override
  Widget build(BuildContext context) {
    final p = path;
    if (p == null || p.isEmpty) {
      return CircleAvatar(radius: radius, backgroundColor: primaryColor.withValues(alpha: 0.12), child: Icon(Icons.person, color: primaryColor, size: radius));
    }
    return CircleAvatar(
      radius: radius,
      backgroundColor: primaryColor.withValues(alpha: 0.12),
      backgroundImage: NetworkImage(assetUrl(p)),
      onBackgroundImageError: (_, __) {},
    );
  }
}

/// Consistent titled card used across booking/account screens.
class SectionCard extends StatelessWidget {
  final String title;
  final List<Widget> children;
  final bool highlight;
  const SectionCard({super.key, required this.title, required this.children, this.highlight = false});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      color: highlight ? primaryColor.withValues(alpha: 0.06) : null,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(title, style: const TextStyle(fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            ...children,
          ],
        ),
      ),
    );
  }
}
