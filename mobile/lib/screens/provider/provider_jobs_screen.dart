import 'package:flutter/material.dart';
import '../../api_client.dart';
import '../../widgets.dart';
import '../login_screen.dart';
import '../notifications_screen.dart';
import '../customer/booking_detail_screen.dart';

class ProviderJobsScreen extends StatefulWidget {
  const ProviderJobsScreen({super.key});

  @override
  State<ProviderJobsScreen> createState() => _ProviderJobsScreenState();
}

class _ProviderJobsScreenState extends State<ProviderJobsScreen> {
  bool? _available;
  bool _togglingAvailability = false;

  Future<void> _toggleAvailability() async {
    setState(() => _togglingAvailability = true);
    try {
      final result = await ApiClient().post('/provider/availability');
      setState(() => _available = result['is_available'] as bool);
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _togglingAvailability = false);
    }
  }

  Future<void> _logout() async {
    final api = ApiClient();
    try {
      await api.post('/logout');
    } catch (_) {}
    await api.clearSession();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const LoginScreen()), (route) => false);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('My Jobs'),
        actions: [
          IconButton(
            icon: const Icon(Icons.notifications_outlined),
            onPressed: () => Navigator.of(context).push(MaterialPageRoute(builder: (_) => const NotificationsScreen())),
          ),
          IconButton(icon: const Icon(Icons.logout), onPressed: _logout),
        ],
      ),
      body: AsyncScreen<List<Map<String, dynamic>>>(
        load: () async {
          final result = await ApiClient().get('/provider/jobs');
          return List<Map<String, dynamic>>.from(result['bookings'] as List);
        },
        builder: (context, jobs, reload) {
          return RefreshIndicator(
            onRefresh: () async => reload(),
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                Card(
                  child: ListTile(
                    title: const Text('Available for new jobs'),
                    subtitle: const Text('Turn off when you\'re done for the day.'),
                    trailing: _togglingAvailability
                        ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2))
                        : Switch(value: _available ?? true, onChanged: (_) => _toggleAvailability()),
                  ),
                ),
                const SizedBox(height: 12),
                if (jobs.isEmpty)
                  const Padding(padding: EdgeInsets.all(32), child: Center(child: Text('No jobs yet.')))
                else
                  ...jobs.map((j) => Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        child: ListTile(
                          contentPadding: const EdgeInsets.all(16),
                          title: Text(j['service_name'] as String, style: const TextStyle(fontWeight: FontWeight.w600)),
                          subtitle: Text('${j['scheduled_date']} · ${j['scheduled_time_slot']} · ${j['city']}'),
                          trailing: StatusBadge(status: j['status'] as String),
                          onTap: () async {
                            await Navigator.of(context).push(
                              MaterialPageRoute(builder: (_) => BookingDetailScreen(bookingId: j['id'] as int)),
                            );
                            reload();
                          },
                        ),
                      )),
              ],
            ),
          );
        },
      ),
    );
  }
}
