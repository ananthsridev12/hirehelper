import 'package:flutter/material.dart';
import '../../api_client.dart';
import '../../widgets.dart';
import 'booking_detail_screen.dart';

class MyBookingsScreen extends StatelessWidget {
  final bool embedded;
  const MyBookingsScreen({super.key, this.embedded = false});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Bookings')),
      body: AsyncScreen<List<Map<String, dynamic>>>(
        load: () async {
          final result = await ApiClient().get('/bookings');
          return List<Map<String, dynamic>>.from(result['bookings'] as List);
        },
        builder: (context, bookings, reload) {
          if (bookings.isEmpty) {
            return const Center(child: Text("You haven't booked any services yet."));
          }
          return RefreshIndicator(
            onRefresh: () async => reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: bookings.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, i) {
                final b = bookings[i];
                return Card(
                  child: ListTile(
                    contentPadding: const EdgeInsets.all(16),
                    title: Text(b['service_name'] as String, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text('${b['scheduled_date']} · ${b['scheduled_time_slot']}'),
                    trailing: StatusBadge(status: b['status'] as String),
                    onTap: () async {
                      await Navigator.of(context).push(
                        MaterialPageRoute(builder: (_) => BookingDetailScreen(bookingId: b['id'] as int)),
                      );
                      reload();
                    },
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
