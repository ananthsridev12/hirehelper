import 'dart:async';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:intl/intl.dart';
import '../../api_client.dart';
import '../../widgets.dart';
import 'booking_screen.dart';

const List<String> _rescheduleSlots = [
  '09:00 AM - 11:00 AM',
  '11:00 AM - 01:00 PM',
  '02:00 PM - 04:00 PM',
  '04:00 PM - 06:00 PM',
  '06:00 PM - 08:00 PM',
];

class BookingDetailScreen extends StatefulWidget {
  final int bookingId;
  const BookingDetailScreen({super.key, required this.bookingId});

  @override
  State<BookingDetailScreen> createState() => _BookingDetailScreenState();
}

class _BookingDetailScreenState extends State<BookingDetailScreen> {
  Map<String, dynamic>? _booking;
  Map<String, dynamic>? _review;
  List<Map<String, dynamic>> _messages = [];
  double? _distanceKm;
  String? _role;
  bool _loading = true;
  bool _acting = false;
  Timer? _locationTimer;
  final _otpController = TextEditingController();
  int _ratingInput = 5;
  final _commentController = TextEditingController();
  final _messageController = TextEditingController();
  DateTime? _rescheduleDate;
  String? _rescheduleSlot;

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
      final booking = result['booking'] as Map<String, dynamic>;
      setState(() {
        _booking = booking;
        _review = result['review'] as Map<String, dynamic>?;
        _distanceKm = result['distance_km'] == null ? null : double.parse('${result['distance_km']}');
        _rescheduleDate = DateTime.tryParse(booking['scheduled_date'] as String);
        _rescheduleSlot = booking['scheduled_time_slot'] as String?;
      });
      if (['assigned', 'in_progress', 'completed'].contains(booking['status'])) {
        _loadMessages();
      }
      _maybeStartLocationPings();
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _loadMessages() async {
    try {
      final result = await ApiClient().get('/bookings/${widget.bookingId}/messages');
      if (mounted) setState(() => _messages = List<Map<String, dynamic>>.from(result['messages'] as List));
    } catch (_) {
      // Non-fatal -- the rest of the booking detail still renders.
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

  Future<void> _sendMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;
    _messageController.clear();
    try {
      final result = await ApiClient().post('/bookings/${widget.bookingId}/messages', {'message': text});
      setState(() => _messages = List<Map<String, dynamic>>.from(result['messages'] as List));
    } catch (e) {
      if (mounted) showError(context, e);
    }
  }

  Future<void> _reschedule() async {
    if (_rescheduleDate == null || _rescheduleSlot == null) return;
    await _call(
      () => ApiClient().post('/bookings/${widget.bookingId}/reschedule', {
        'scheduled_date': DateFormat('yyyy-MM-dd').format(_rescheduleDate!),
        'scheduled_time_slot': _rescheduleSlot,
      }),
      successMessage: 'Booking rescheduled.',
    );
  }

  Future<void> _rebook() async {
    final serviceSlug = _booking?['service_slug'] as String?;
    if (serviceSlug == null) return;
    try {
      final result = await ApiClient().get('/services/$serviceSlug');
      if (!mounted) return;
      Navigator.of(context).push(MaterialPageRoute(builder: (_) => BookingScreen(service: result['service'] as Map<String, dynamic>)));
    } catch (e) {
      if (mounted) showError(context, e);
    }
  }

  Future<void> _reportIssue() async {
    final controller = TextEditingController();
    final message = await showDialog<String>(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Report an issue'),
        content: TextField(
          controller: controller,
          maxLines: 3,
          autofocus: true,
          decoration: const InputDecoration(hintText: "Describe the issue and we'll look into it.", border: OutlineInputBorder()),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.of(context).pop(), child: const Text('Cancel')),
          FilledButton(onPressed: () => Navigator.of(context).pop(controller.text.trim()), child: const Text('Submit')),
        ],
      ),
    );
    if (message == null || message.isEmpty) return;
    try {
      await ApiClient().post('/bookings/${widget.bookingId}/report-issue', {'message': message});
      if (mounted) showSuccess(context, "Thanks -- we'll look into it and get back to you.");
    } catch (e) {
      if (mounted) showError(context, e);
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
    final price = double.parse('${b['price']}');
    final discount = double.parse('${b['discount_amount'] ?? 0}');

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
            SectionCard(title: 'Schedule', children: [
              _row('Date', '${b['scheduled_date']}'),
              _row('Time slot', '${b['scheduled_time_slot']}'),
              if (discount > 0) ...[
                Text.rich(TextSpan(children: [
                  const TextSpan(text: 'Price: ', style: TextStyle(fontWeight: FontWeight.w600)),
                  TextSpan(text: formatMoney(price - discount)),
                  TextSpan(text: '  ${formatMoney(price)}', style: const TextStyle(decoration: TextDecoration.lineThrough, color: Colors.grey)),
                  TextSpan(text: ' (${formatMoney(discount)} off)', style: const TextStyle(color: Colors.green)),
                ])),
              ] else
                _row('Price', formatMoney(price)),
              if ((b['notes'] as String?)?.isNotEmpty == true) _row('Notes', b['notes'] as String),
            ]),
            SectionCard(title: 'Address', children: [
              Text('${b['address_label']}\n${b['line1']}${(b['line2'] as String? ?? '').isNotEmpty ? ', ${b['line2']}' : ''}\n${b['city']}, ${b['state']} - ${b['pincode']}'),
            ]),
            SectionCard(title: 'People', children: [
              _row('Customer', '${b['customer_name']} (${b['customer_phone']})'),
              _row('Professional', b['provider_name'] != null ? '${b['provider_name']} (${b['provider_phone']})' : 'Not yet assigned'),
            ]),

            if ((b['before_photo_path'] != null) || (b['after_photo_path'] != null))
              SectionCard(title: 'Job photos', children: [
                Row(children: [
                  if (b['before_photo_path'] != null) _photoThumb(b['before_photo_path'] as String, 'Before'),
                  if (b['before_photo_path'] != null && b['after_photo_path'] != null) const SizedBox(width: 12),
                  if (b['after_photo_path'] != null) _photoThumb(b['after_photo_path'] as String, 'After'),
                ]),
              ]),

            if (isCustomer && status == 'assigned' && b['start_otp'] != null)
              SectionCard(title: 'Your start code', highlight: true, children: [
                const Text('Share this with your professional when they arrive.'),
                const SizedBox(height: 8),
                Text('${b['start_otp']}', style: const TextStyle(fontSize: 28, fontWeight: FontWeight.bold, letterSpacing: 4)),
              ]),

            if (isCustomer && status == 'in_progress')
              SectionCard(title: 'Job in progress', children: [
                Text(_distanceKm != null
                    ? 'Your professional is about ${_distanceKm!.toStringAsFixed(1)} km away.'
                    : 'Live distance will appear once your professional starts sharing location.'),
              ]),

            if (isCustomer && ['pending', 'offered', 'assigned'].contains(status)) _buildRescheduleSection(b),

            if (isProvider && status == 'offered')
              Row(children: [
                Expanded(child: _actionButton('Accept', primaryColor, () => _call(() => ApiClient().post('/provider/jobs/${b['id']}/accept'), successMessage: 'Job accepted.'))),
                const SizedBox(width: 12),
                Expanded(child: OutlinedButton(onPressed: _acting ? null : () => _call(() => ApiClient().post('/provider/jobs/${b['id']}/reject')), child: const Text('Decline'))),
              ]),

            if (isProvider && status == 'assigned') ...[
              SectionCard(title: 'Start the job', children: [
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
              OutlinedButton.icon(
                onPressed: _acting ? null : () => _call(() => ApiClient().post('/provider/jobs/${b['id']}/notify-delay'), successMessage: 'Customer notified.'),
                icon: const Icon(Icons.schedule),
                label: const Text('Running late? Notify customer'),
              ),
              const SizedBox(height: 16),
            ],

            if (isProvider && status == 'in_progress')
              _actionButton('Mark Completed', primaryColor, () => _call(() => ApiClient().post('/provider/jobs/${b['id']}/complete'), successMessage: 'Job marked complete.')),

            if (isCustomer && status == 'completed') _buildReviewSection(b),

            if (['assigned', 'in_progress', 'completed'].contains(status) && (isCustomer || isProvider)) _buildChatSection(),

            if (isCustomer)
              Padding(
                padding: const EdgeInsets.only(top: 4, bottom: 16),
                child: OutlinedButton.icon(
                  onPressed: _reportIssue,
                  icon: const Icon(Icons.flag_outlined),
                  label: const Text('Having a problem with this booking?'),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildRescheduleSection(Map<String, dynamic> b) {
    return SectionCard(title: 'Need to change something?', children: [
      Row(children: [
        Expanded(
          child: OutlinedButton(
            onPressed: () async {
              final picked = await showDatePicker(
                context: context,
                initialDate: _rescheduleDate ?? DateTime.now(),
                firstDate: DateTime.now(),
                lastDate: DateTime.now().add(const Duration(days: 60)),
              );
              if (picked != null) setState(() => _rescheduleDate = picked);
            },
            child: Text(_rescheduleDate == null ? 'Choose date' : DateFormat('d MMM yyyy').format(_rescheduleDate!)),
          ),
        ),
      ]),
      const SizedBox(height: 10),
      Wrap(
        spacing: 8,
        runSpacing: 8,
        children: _rescheduleSlots.map((slot) => ChoiceChip(
              label: Text(slot, style: const TextStyle(fontSize: 11)),
              selected: _rescheduleSlot == slot,
              onSelected: (_) => setState(() => _rescheduleSlot = slot),
            )).toList(),
      ),
      const SizedBox(height: 12),
      Row(children: [
        Expanded(child: _actionButton('Reschedule', primaryColor, _reschedule)),
        const SizedBox(width: 12),
        Expanded(
          child: OutlinedButton(
            onPressed: _acting ? null : () => _call(() => ApiClient().post('/bookings/${b['id']}/cancel'), successMessage: 'Booking cancelled.'),
            style: OutlinedButton.styleFrom(foregroundColor: Colors.red, side: const BorderSide(color: Colors.red)),
            child: const Text('Cancel'),
          ),
        ),
      ]),
    ]);
  }

  Widget _buildReviewSection(Map<String, dynamic> b) {
    if (_review != null) {
      return SectionCard(title: 'Your review', children: [
        Row(children: List.generate(5, (i) => Icon(
              i < (_review!['rating'] as int) ? Icons.star : Icons.star_border,
              color: Colors.amber,
              size: 20,
            ))),
        const SizedBox(height: 8),
        Text('${_review!['comment']}'),
        if ((_review!['photo_path'] as String?)?.isNotEmpty == true) ...[
          const SizedBox(height: 12),
          ClipRRect(
            borderRadius: BorderRadius.circular(8),
            child: SizedBox(height: 140, child: RemoteThumb(path: _review!['photo_path'] as String, fallback: const SizedBox())),
          ),
        ],
        const SizedBox(height: 12),
        OutlinedButton.icon(onPressed: _rebook, icon: const Icon(Icons.replay), label: const Text('Book this again')),
      ]);
    }
    return SectionCard(title: 'Rate this service', children: [
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
      const SizedBox(height: 4),
      OutlinedButton.icon(onPressed: _rebook, icon: const Icon(Icons.replay), label: const Text('Book this again')),
    ]);
  }

  Widget _buildChatSection() {
    return SectionCard(title: 'Messages', children: [
      if (_messages.isEmpty)
        const Padding(padding: EdgeInsets.symmetric(vertical: 8), child: Text('No messages yet. Say hello!', style: TextStyle(color: Colors.grey)))
      else
        ..._messages.map((m) {
          final mine = (m['sender_role'] as String?) == _role;
          return Align(
            alignment: mine ? Alignment.centerRight : Alignment.centerLeft,
            child: Container(
              margin: const EdgeInsets.only(bottom: 8),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              constraints: const BoxConstraints(maxWidth: 260),
              decoration: BoxDecoration(
                color: mine ? primaryColor : Colors.grey.shade200,
                borderRadius: BorderRadius.circular(12),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(m['message'] as String, style: TextStyle(color: mine ? Colors.white : Colors.black87)),
                  const SizedBox(height: 2),
                  Text(
                    '${mine ? 'You' : m['sender_name']}',
                    style: TextStyle(fontSize: 11, color: mine ? Colors.white70 : Colors.grey),
                  ),
                ],
              ),
            ),
          );
        }),
      const SizedBox(height: 8),
      Row(children: [
        Expanded(
          child: TextField(
            controller: _messageController,
            decoration: const InputDecoration(hintText: 'Type a message...', border: OutlineInputBorder(), isDense: true),
            onSubmitted: (_) => _sendMessage(),
          ),
        ),
        const SizedBox(width: 8),
        IconButton.filled(onPressed: _sendMessage, icon: const Icon(Icons.send)),
      ]),
    ]);
  }

  Widget _photoThumb(String path, String label) {
    return Expanded(
      child: Column(children: [
        ClipRRect(
          borderRadius: BorderRadius.circular(8),
          child: AspectRatio(aspectRatio: 1, child: RemoteThumb(path: path, fallback: Container(color: Colors.grey.shade200))),
        ),
        const SizedBox(height: 4),
        Text(label, style: const TextStyle(fontSize: 12, color: Colors.grey)),
      ]),
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
