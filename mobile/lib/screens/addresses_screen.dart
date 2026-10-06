import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import '../api_client.dart';
import '../widgets.dart';

class AddressesScreen extends StatefulWidget {
  const AddressesScreen({super.key});

  @override
  State<AddressesScreen> createState() => _AddressesScreenState();
}

class _AddressesScreenState extends State<AddressesScreen> {
  List<Map<String, dynamic>> _addresses = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final result = await ApiClient().get('/addresses');
      setState(() => _addresses = List<Map<String, dynamic>>.from(result['addresses'] as List));
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _delete(int id) async {
    try {
      await ApiClient().post('/addresses/$id/delete');
      _load();
    } catch (e) {
      if (mounted) showError(context, e);
    }
  }

  Future<void> _openAddSheet() async {
    final added = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (_) => const _AddAddressSheet(),
    );
    if (added == true) _load();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('My Addresses')),
      floatingActionButton: FloatingActionButton(onPressed: _openAddSheet, child: const Icon(Icons.add)),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : _addresses.isEmpty
              ? const Center(child: Text('No saved addresses yet. Tap + to add one.'))
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: _addresses.length,
                  itemBuilder: (context, i) {
                    final a = _addresses[i];
                    return Card(
                      child: ListTile(
                        title: Row(children: [
                          Text(a['label'] as String, style: const TextStyle(fontWeight: FontWeight.w600)),
                          if (a['is_default'] == 1 || a['is_default'] == true) ...[
                            const SizedBox(width: 8),
                            const StatusBadge(status: 'assigned'),
                          ],
                        ]),
                        subtitle: Text('${a['line1']}, ${a['city']} - ${a['pincode']}'),
                        trailing: IconButton(
                          icon: const Icon(Icons.delete_outline, color: Colors.red),
                          onPressed: () => _delete(a['id'] as int),
                        ),
                      ),
                    );
                  },
                ),
    );
  }
}

class _AddAddressSheet extends StatefulWidget {
  const _AddAddressSheet();

  @override
  State<_AddAddressSheet> createState() => _AddAddressSheetState();
}

class _AddAddressSheetState extends State<_AddAddressSheet> {
  final _labelController = TextEditingController(text: 'Home');
  final _line1Controller = TextEditingController();
  final _line2Controller = TextEditingController();
  final _cityController = TextEditingController();
  final _stateController = TextEditingController();
  final _pincodeController = TextEditingController();
  final _phoneController = TextEditingController();

  double? _lat;
  double? _lng;
  bool _locating = false;
  bool _submitting = false;

  Future<void> _useCurrentLocation() async {
    setState(() => _locating = true);
    try {
      final permission = await Geolocator.checkPermission();
      var granted = permission;
      if (granted == LocationPermission.denied) {
        granted = await Geolocator.requestPermission();
      }
      if (granted == LocationPermission.denied || granted == LocationPermission.deniedForever) {
        throw 'Location permission denied. Enable it in your device settings.';
      }
      if (!await Geolocator.isLocationServiceEnabled()) {
        throw 'Turn on device location (GPS) and try again.';
      }

      final position = await Geolocator.getCurrentPosition(desiredAccuracy: LocationAccuracy.high);
      setState(() {
        _lat = position.latitude;
        _lng = position.longitude;
      });
      if (mounted) showSuccess(context, 'Location captured: ${_lat!.toStringAsFixed(5)}, ${_lng!.toStringAsFixed(5)}');
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _submit() async {
    if (_line1Controller.text.trim().isEmpty || _cityController.text.trim().isEmpty) {
      showError(context, 'Address line and city are required.');
      return;
    }
    setState(() => _submitting = true);
    try {
      await ApiClient().post('/addresses', {
        'label': _labelController.text.trim(),
        'line1': _line1Controller.text.trim(),
        'line2': _line2Controller.text.trim(),
        'city': _cityController.text.trim(),
        'state': _stateController.text.trim(),
        'pincode': _pincodeController.text.trim(),
        'phone': _phoneController.text.trim(),
        'lat': _lat,
        'lng': _lng,
        'is_default': true,
      });
      if (mounted) Navigator.of(context).pop(true);
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 20, right: 20, top: 20,
        bottom: 20 + MediaQuery.of(context).viewInsets.bottom,
      ),
      child: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          mainAxisSize: MainAxisSize.min,
          children: [
            const Text('Add address', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
            const SizedBox(height: 16),
            OutlinedButton.icon(
              onPressed: _locating ? null : _useCurrentLocation,
              icon: _locating
                  ? const SizedBox(height: 16, width: 16, child: CircularProgressIndicator(strokeWidth: 2))
                  : const Icon(Icons.my_location),
              label: Text(_lat == null ? 'Use my current GPS location' : 'Location captured ✓'),
            ),
            const SizedBox(height: 16),
            TextField(controller: _labelController, decoration: const InputDecoration(labelText: 'Label', border: OutlineInputBorder())),
            const SizedBox(height: 12),
            TextField(controller: _line1Controller, decoration: const InputDecoration(labelText: 'Address line 1', border: OutlineInputBorder())),
            const SizedBox(height: 12),
            TextField(controller: _line2Controller, decoration: const InputDecoration(labelText: 'Address line 2 (optional)', border: OutlineInputBorder())),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: TextField(controller: _cityController, decoration: const InputDecoration(labelText: 'City', border: OutlineInputBorder()))),
              const SizedBox(width: 12),
              Expanded(child: TextField(controller: _stateController, decoration: const InputDecoration(labelText: 'State', border: OutlineInputBorder()))),
            ]),
            const SizedBox(height: 12),
            Row(children: [
              Expanded(child: TextField(controller: _pincodeController, decoration: const InputDecoration(labelText: 'Pincode', border: OutlineInputBorder()))),
              const SizedBox(width: 12),
              Expanded(child: TextField(controller: _phoneController, decoration: const InputDecoration(labelText: 'Contact phone', border: OutlineInputBorder()))),
            ]),
            const SizedBox(height: 20),
            FilledButton(
              onPressed: _submitting ? null : _submit,
              style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
              child: _submitting
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Text('Save Address'),
            ),
          ],
        ),
      ),
    );
  }
}
