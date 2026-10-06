import 'package:flutter/material.dart';

const Color primaryColor = Color(0xFF4F46E5);

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
