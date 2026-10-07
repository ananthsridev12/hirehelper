import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
import '../../api_client.dart';
import '../../widgets.dart';
import '../addresses_screen.dart';
import 'booking_detail_screen.dart';

const List<String> timeSlots = [
  '09:00 AM - 11:00 AM',
  '11:00 AM - 01:00 PM',
  '02:00 PM - 04:00 PM',
  '04:00 PM - 06:00 PM',
  '06:00 PM - 08:00 PM',
];

class BookingScreen extends StatefulWidget {
  final Map<String, dynamic> service;
  const BookingScreen({super.key, required this.service});

  @override
  State<BookingScreen> createState() => _BookingScreenState();
}

class _BookingScreenState extends State<BookingScreen> {
  List<Map<String, dynamic>> _addresses = [];
  bool _loadingAddresses = true;
  int? _selectedAddressId;
  DateTime? _selectedDate;
  String? _selectedSlot;
  final _notesController = TextEditingController();
  final _couponController = TextEditingController();
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _loadAddresses();
  }

  Future<void> _loadAddresses() async {
    setState(() => _loadingAddresses = true);
    try {
      final result = await ApiClient().get('/addresses');
      final addresses = List<Map<String, dynamic>>.from(result['addresses'] as List);
      setState(() {
        _addresses = addresses;
        _selectedAddressId = addresses.isNotEmpty ? addresses.first['id'] as int : null;
      });
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _loadingAddresses = false);
    }
  }

  Future<void> _pickDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 60)),
    );
    if (picked != null) setState(() => _selectedDate = picked);
  }

  Future<void> _submit() async {
    if (_selectedAddressId == null || _selectedDate == null || _selectedSlot == null) {
      showError(context, 'Please choose an address, date and time slot.');
      return;
    }
    setState(() => _submitting = true);
    try {
      final result = await ApiClient().post('/services/${widget.service['slug']}/book', {
        'address_id': _selectedAddressId,
        'scheduled_date': DateFormat('yyyy-MM-dd').format(_selectedDate!),
        'scheduled_time_slot': _selectedSlot,
        'notes': _notesController.text.trim(),
        'coupon_code': _couponController.text.trim(),
      });
      final booking = result['booking'] as Map<String, dynamic>;
      if (!mounted) return;
      showSuccess(context, 'Booking placed!');
      Navigator.of(context).pushReplacement(
        MaterialPageRoute(builder: (_) => BookingDetailScreen(bookingId: booking['id'] as int)),
      );
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('Book: ${widget.service['name']}')),
      body: _loadingAddresses
          ? const Center(child: CircularProgressIndicator())
          : _addresses.isEmpty
              ? Center(
                  child: Padding(
                    padding: const EdgeInsets.all(24),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Text("You don't have a saved address yet."),
                        const SizedBox(height: 12),
                        FilledButton(
                          onPressed: () async {
                            await Navigator.of(context).push(MaterialPageRoute(builder: (_) => const AddressesScreen()));
                            _loadAddresses();
                          },
                          child: const Text('Add an address'),
                        ),
                      ],
                    ),
                  ),
                )
              : ListView(
                  padding: const EdgeInsets.all(20),
                  children: [
                    const Text('Deliver service at', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 8),
                    ..._addresses.map((a) => RadioListTile<int>(
                          contentPadding: EdgeInsets.zero,
                          value: a['id'] as int,
                          groupValue: _selectedAddressId,
                          onChanged: (v) => setState(() => _selectedAddressId = v),
                          title: Text('${a['label']} - ${a['line1']}'),
                          subtitle: Text('${a['city']} ${a['pincode']}'),
                        )),
                    const SizedBox(height: 16),
                    const Text('Preferred date', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 8),
                    OutlinedButton(
                      onPressed: _pickDate,
                      child: Text(_selectedDate == null ? 'Choose a date' : DateFormat('EEE, d MMM yyyy').format(_selectedDate!)),
                    ),
                    const SizedBox(height: 16),
                    const Text('Preferred time slot', style: TextStyle(fontWeight: FontWeight.w600)),
                    const SizedBox(height: 8),
                    Wrap(
                      spacing: 8,
                      runSpacing: 8,
                      children: timeSlots.map((slot) => ChoiceChip(
                            label: Text(slot),
                            selected: _selectedSlot == slot,
                            onSelected: (_) => setState(() => _selectedSlot = slot),
                          )).toList(),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _notesController,
                      maxLines: 3,
                      decoration: const InputDecoration(
                        labelText: 'Notes for the professional (optional)',
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 16),
                    TextField(
                      controller: _couponController,
                      textCapitalization: TextCapitalization.characters,
                      decoration: const InputDecoration(
                        labelText: 'Coupon code (optional)',
                        prefixIcon: Icon(Icons.local_offer_outlined),
                        border: OutlineInputBorder(),
                      ),
                    ),
                    const SizedBox(height: 24),
                    FilledButton(
                      onPressed: _submitting ? null : _submit,
                      style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
                      child: _submitting
                          ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                          : const Text('Confirm Booking'),
                    ),
                  ],
                ),
    );
  }
}
