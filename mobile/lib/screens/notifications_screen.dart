import 'package:flutter/material.dart';
import '../api_client.dart';
import '../widgets.dart';
import 'customer/booking_detail_screen.dart';

class NotificationsScreen extends StatelessWidget {
  const NotificationsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Notifications'),
        actions: [
          IconButton(
            icon: const Icon(Icons.done_all),
            tooltip: 'Mark all read',
            onPressed: () async {
              try {
                await ApiClient().post('/notifications/mark-read');
                if (context.mounted) Navigator.of(context).pop();
              } catch (e) {
                if (context.mounted) showError(context, e);
              }
            },
          ),
        ],
      ),
      body: AsyncScreen<List<Map<String, dynamic>>>(
        load: () async {
          final result = await ApiClient().get('/notifications');
          return List<Map<String, dynamic>>.from(result['notifications'] as List);
        },
        builder: (context, notifications, reload) {
          if (notifications.isEmpty) {
            return const Center(child: Text('No notifications yet.'));
          }
          return ListView.separated(
            itemCount: notifications.length,
            separatorBuilder: (_, __) => const Divider(height: 1),
            itemBuilder: (context, i) {
              final n = notifications[i];
              return ListTile(
                leading: Icon(
                  (n['is_read'] == 1 || n['is_read'] == true) ? Icons.notifications_none : Icons.notifications_active,
                  color: (n['is_read'] == 1 || n['is_read'] == true) ? Colors.grey : primaryColor,
                ),
                title: Text(n['title'] as String, style: const TextStyle(fontWeight: FontWeight.w600)),
                subtitle: Text(n['body'] as String),
                onTap: n['booking_id'] != null
                    ? () => Navigator.of(context).push(
                          MaterialPageRoute(builder: (_) => BookingDetailScreen(bookingId: n['booking_id'] as int)),
                        )
                    : null,
              );
            },
          );
        },
      ),
    );
  }
}
