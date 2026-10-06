import 'package:flutter/material.dart';
import '../../api_client.dart';
import '../../widgets.dart';
import 'booking_screen.dart';

class ServiceDetailScreen extends StatelessWidget {
  final String slug;
  const ServiceDetailScreen({super.key, required this.slug});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Service details')),
      body: AsyncScreen<Map<String, dynamic>>(
        load: () => ApiClient().get('/services/$slug'),
        builder: (context, result, reload) {
          final service = result['service'] as Map<String, dynamic>;
          final rating = result['rating'] as Map<String, dynamic>;
          final avg = rating['avg_rating'];

          return Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(service['name'] as String, style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold)),
                if (avg != null) ...[
                  const SizedBox(height: 6),
                  Row(children: [
                    const Icon(Icons.star, color: Colors.amber, size: 18),
                    const SizedBox(width: 4),
                    Text('${double.parse('$avg').toStringAsFixed(1)} · ${rating['review_count']} reviews'),
                  ]),
                ],
                const SizedBox(height: 16),
                Text(service['description'] as String? ?? '', style: const TextStyle(height: 1.5)),
                const SizedBox(height: 8),
                Text('Estimated duration: ${service['duration_minutes']} minutes', style: const TextStyle(color: Colors.grey)),
                const Spacer(),
                Row(
                  children: [
                    Text(
                      formatMoney(double.parse('${service['price']}')),
                      style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    FilledButton(
                      style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 16)),
                      onPressed: () => Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => BookingScreen(service: service)),
                      ),
                      child: const Text('Book Now'),
                    ),
                  ],
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
