import 'dart:async';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import '../../api_client.dart';
import '../../widgets.dart';

class BookingDetailScreen extends StatefulWidget {
  final int bookingId;
  const BookingDetailScreen({super.key, required this.bookingId});

  @override
  State<BookingDetailScreen> createState() => _BookingDetailScreenState();
}

class _BookingDetailScreenState extends State<BookingDetailScreen> {
  Map<String, dynamic>? _booking;
  Map<String, dynamic>? _review;
  double? _distanceKm;
  String? _role;
  bool _loading = true;
  bool _acting = false;
  Timer? _locationTimer;
  final _otpController = TextEditingController();
  int _ratingInput = 5;
  final _commentController = TextEditingController();

  @override
  void initState() {
    super.initState();
    ApiClient().getRole().then((r) => setState(() => _role = r));
    _load();
  }

  @override
  void dispose() {
    _locationTimer?.cancel();
    super.dispose();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final result = await ApiClient().get('/bookings/${widget.bookingId}');
      setState(() {
        _booking = result['booking'] as Map<String, dynamic>;
        _review = result['review'] as Map<String, dynamic>?;
        _distanceKm = result['distance_km'] == null ? null : double.parse('${result['distance_km']}');
      });
      _maybeStartLocationPings();
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  void _maybeStartLocationPings() {
    _locationTimer?.cancel();
    if (_role != 'provider' || _booking?['status'] != 'in_progress') return;
    _pingLocationOnce();
    _locationTimer = Timer.periodic(const Duration(seconds: 45), (_) => _pingLocationOnce());
  }

  Future<void> _pingLocationOnce() async {
    try {
      final permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        await Geolocator.requestPermission();
      }
      final position = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.medium);
      await ApiClient().post('/provider/location-ping', {'lat': position.latitude, 'lng': position.longitude});
    } catch (_) {
      // Best-effort -- a missed ping just means the customer's distance
      // reading is a little stale, nothing to surface to the provider.
    }
  }

  Future<void> _call(Future<Map<String, dynamic>> Function() action, {String? successMessage}) async {
    setState(() => _acting = true);
    try {
      await action();
      if (successMessage != null && mounted) showSuccess(context, successMessage);
      await _load();
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _acting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading || _booking == null) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }
    final b = _booking!;
    final status = b['status'] as String;
    final isCustomer = _role == 'customer';
    final isProvider = _role == 'provider';

    return Scaffold(
      appBar: AppBar(title: Text('Booking #${b['id']}')),
      body: RefreshIndicator(
        onRefresh: _load,
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            Row(mainAxisAlignment: MainAxisAlignment.spaceBetween, children: [
              Expanded(child: Text(b['service_name'] as String, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold))),
              StatusBadge(status: status),
            ]),
            const SizedBox(height: 4),
            Text(b['category_name'] as String, style: const TextStyle(color: Colors.grey)),
            const SizedBox(height: 16),
            _sectionCard('Schedule', [
              _row('Date', '${b['scheduled_date']}'),
              _row('Time slot', '${b['scheduled_time_slot']}'),
              _row('Price', formatMoney(double.parse('${b['price']}'))),
              if ((b['notes'] as String?)?.isNotEmpty == true) _row('Notes', b['notes'] as String),
            ]),
            _sectionCard('Address', [
              Text('${b['address_label']}\n${b['line1']}${(b['line2'] as String).isNotEmpty ? ', ${b['line2']}' : ''}\n${b['city']}, ${b['state']} - ${b['pincode']}'),
            ]),
            _sectionCard('People', [
              _row('Customer', '${b['customer_name']} (${b['customer_phone']})'),
              _row('Professional', b['provider_name'] != null ? '${b['provider_name']} (${b['provider_phone']})' : 'Not yet assigned'),
            ]),

            if (isCustomer && status == 'assigned' && b['start_otp'] != null)
              _sectionCard('Your start code', [
                const Text('Share this with your professional when they arrive.'),
                const SizedBox(height: 8),
                Text('${b['start_otp']}', style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, letterSpacing: 4)),
              ], highlight: true),

            if (isCustomer && status == 'in_progress')
              _sectionCard('Job in progress', [
                Text(_distanceKm != null
                    ? 'Your professional is about ${_distanceKm!.toStringAsFixed(1)} km away.'
                    : 'Live distance will appear once your professional starts sharing location.'),
              ]),

            if (isCustomer && ['pending', 'offered', 'assigned'].contains(status))
              _actionButton('Cancel Booking', Colors.red, () => _call(() => ApiClient().post('/bookings/${b['id']}/cancel'), successMessage: 'Booking cancelled.')),

            if (isProvider && status == 'offered')
              Row(children: [
                Expanded(child: _actionButton('Accept', primaryColor, () => _call(() => ApiClient().post('/provider/jobs/${b['id']}/accept'), successMessage: 'Job accepted.'))),
                const SizedBox(width: 12),
                Expanded(child: OutlinedButton(onPressed: _acting ? null : () => _call(() => ApiClient().post('/provider/jobs/${b['id']}/reject')), child: const Text('Decline'))),
              ]),

            if (isProvider && status == 'assigned')
              _sectionCard('Start the job', [
                const Text('Ask the customer for their start code.'),
                const SizedBox(height: 8),
                TextField(
                  controller: _otpController,
                  keyboardType: TextInputType.number,
                  maxLength: 4,
                  decoration: const InputDecoration(labelText: 'Start code', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 12),
                _actionButton('Start Job', primaryColor, () => _call(
                      () => ApiClient().post('/provider/jobs/${b['id']}/start', {'start_otp': _otpController.text.trim()}),
                      successMessage: 'Job started.',
                    )),
              ]),

            if (isProvider && status == 'in_progress')
              _actionButton('Mark Completed', primaryColor, () => _call(() => ApiClient().post('/provider/jobs/${b['id']}/complete'), successMessage: 'Job marked complete.')),

            if (isCustomer && status == 'completed') _buildReviewSection(b),
          ],
        ),
      ),
    );
  }

  Widget _buildReviewSection(Map<String, dynamic> b) {
    if (_review != null) {
      return _sectionCard('Your review', [
        Row(children: List.generate(5, (i) => Icon(
              i < (_review!['rating'] as int) ? Icons.star : Icons.star_border,
              color: Colors.amber,
              size: 20,
            ))),
        const SizedBox(height: 8),
        Text('${_review!['comment']}'),
      ]);
    }
    return _sectionCard('Rate this service', [
      Row(
        children: List.generate(5, (i) {
          final star = i + 1;
          return IconButton(
            icon: Icon(star <= _ratingInput ? Icons.star : Icons.star_border, color: Colors.amber),
            onPressed: () => setState(() => _ratingInput = star),
          );
        }),
      ),
      TextField(
        controller: _commentController,
        maxLines: 3,
        decoration: const InputDecoration(labelText: 'How was your experience?', border: OutlineInputBorder()),
      ),
      const SizedBox(height: 12),
      _actionButton('Submit Review', primaryColor, () => _call(
            () => ApiClient().post('/bookings/${b['id']}/review', {'rating': _ratingInput, 'comment': _commentController.text.trim()}),
            successMessage: 'Thanks for your feedback!',
          )),
    ]);
  }

  Widget _sectionCard(String title, List<Widget> children, {bool highlight = false}) {
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

  Widget _row(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 4),
      child: Text.rich(TextSpan(children: [
        TextSpan(text: '$label: ', style: const TextStyle(fontWeight: FontWeight.w600)),
        TextSpan(text: value),
      ])),
    );
  }

  Widget _actionButton(String label, Color color, VoidCallback onPressed) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: FilledButton(
        onPressed: _acting ? null : onPressed,
        style: FilledButton.styleFrom(backgroundColor: color, padding: const EdgeInsets.symmetric(vertical: 14)),
        child: _acting
            ? const SizedBox(height: 18, width: 18, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
            : Text(label),
      ),
    );
  }
}
