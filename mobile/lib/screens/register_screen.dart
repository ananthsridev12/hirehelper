import 'package:flutter/material.dart';
import '../api_client.dart';
import '../widgets.dart';
import 'customer/customer_home_screen.dart';
import 'provider/provider_jobs_screen.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen> {
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _passwordController = TextEditingController();
  final _cityController = TextEditingController();
  final _referralController = TextEditingController();

  String _role = 'customer';
  bool _loading = false;
  List<Map<String, dynamic>> _categories = [];
  final Set<int> _selectedCategoryIds = {};

  @override
  void initState() {
    super.initState();
    _loadCategories();
  }

  Future<void> _loadCategories() async {
    try {
      final result = await ApiClient().get('/categories');
      setState(() => _categories = List<Map<String, dynamic>>.from(result['categories'] as List));
    } catch (_) {
      // Non-fatal: provider signup just won't have a category list yet.
    }
  }

  Future<void> _submit() async {
    setState(() => _loading = true);
    final api = ApiClient();
    try {
      final result = await api.post('/register', {
        'name': _nameController.text.trim(),
        'email': _emailController.text.trim(),
        'phone': _phoneController.text.trim(),
        'password': _passwordController.text,
        'role': _role,
        'city': _cityController.text.trim(),
        'categories': _selectedCategoryIds.toList(),
        'device_label': 'flutter-app',
        'referral_code': _referralController.text.trim(),
      });
      final user = result['user'] as Map<String, dynamic>;
      await api.saveSession(user, result['token'] as String);
      if (!mounted) return;
      if (user['role'] == 'provider') {
        Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const ProviderJobsScreen()), (route) => false);
      } else {
        Navigator.of(context).pushAndRemoveUntil(MaterialPageRoute(builder: (_) => const CustomerHomeScreen()), (route) => false);
      }
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Create account')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            SegmentedButton<String>(
              segments: const [
                ButtonSegment(value: 'customer', label: Text('Book services')),
                ButtonSegment(value: 'provider', label: Text('Offer services')),
              ],
              selected: {_role},
              onSelectionChanged: (s) => setState(() => _role = s.first),
            ),
            const SizedBox(height: 20),
            TextField(controller: _nameController, decoration: const InputDecoration(labelText: 'Full name', border: OutlineInputBorder())),
            const SizedBox(height: 16),
            TextField(controller: _phoneController, keyboardType: TextInputType.phone, decoration: const InputDecoration(labelText: 'Phone', border: OutlineInputBorder())),
            const SizedBox(height: 16),
            TextField(controller: _emailController, keyboardType: TextInputType.emailAddress, decoration: const InputDecoration(labelText: 'Email', border: OutlineInputBorder())),
            const SizedBox(height: 16),
            TextField(controller: _passwordController, obscureText: true, decoration: const InputDecoration(labelText: 'Password (min 8 characters)', border: OutlineInputBorder())),
            const SizedBox(height: 16),
            TextField(
              controller: _referralController,
              textCapitalization: TextCapitalization.characters,
              decoration: const InputDecoration(
                labelText: 'Referral code (optional)',
                helperText: "Got a code from a friend? You'll both get a wallet bonus.",
                border: OutlineInputBorder(),
              ),
            ),
            if (_role == 'provider') ...[
              const SizedBox(height: 16),
              TextField(controller: _cityController, decoration: const InputDecoration(labelText: 'City you serve', border: OutlineInputBorder())),
              const SizedBox(height: 16),
              const Align(alignment: Alignment.centerLeft, child: Text('Service categories', style: TextStyle(fontWeight: FontWeight.w600))),
              Wrap(
                spacing: 8,
                children: _categories.map((c) {
                  final id = c['id'] as int;
                  final selected = _selectedCategoryIds.contains(id);
                  return FilterChip(
                    label: Text(c['name'] as String),
                    selected: selected,
                    onSelected: (v) => setState(() => v ? _selectedCategoryIds.add(id) : _selectedCategoryIds.remove(id)),
                  );
                }).toList(),
              ),
            ],
            const SizedBox(height: 24),
            FilledButton(
              onPressed: _loading ? null : _submit,
              style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
              child: _loading
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Text('Create account'),
            ),
          ],
        ),
      ),
    );
  }
}
