import 'package:flutter/material.dart';
import '../../api_client.dart';
import '../../widgets.dart';

class ProviderProfileScreen extends StatefulWidget {
  const ProviderProfileScreen({super.key});

  @override
  State<ProviderProfileScreen> createState() => _ProviderProfileScreenState();
}

class _ProviderProfileScreenState extends State<ProviderProfileScreen> {
  final _bioController = TextEditingController();
  final _experienceController = TextEditingController();
  Map<String, dynamic>? _profile;
  Map<String, dynamic>? _rating;
  bool _loading = true;
  bool _saving = false;

  @override
  void initState() {
    super.initState();
    _load();
  }

  Future<void> _load() async {
    setState(() => _loading = true);
    try {
      final result = await ApiClient().get('/provider/profile');
      final profile = result['profile'] as Map<String, dynamic>?;
      setState(() {
        _profile = profile;
        _rating = result['rating'] as Map<String, dynamic>?;
        _bioController.text = profile?['bio'] as String? ?? '';
        _experienceController.text = profile?['experience_years']?.toString() ?? '';
      });
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  Future<void> _save() async {
    setState(() => _saving = true);
    try {
      await ApiClient().post('/provider/profile', {
        'bio': _bioController.text.trim(),
        'experience_years': _experienceController.text.trim(),
      });
      if (mounted) showSuccess(context, 'Profile updated.');
      await _load();
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _saving = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final avgRating = _rating?['avg_rating'];
    final reviewCount = _rating?['review_count'] ?? 0;

    return Scaffold(
      appBar: AppBar(title: const Text('My Profile')),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : ListView(
              padding: const EdgeInsets.all(20),
              children: [
                Center(child: AvatarThumb(path: _profile?['photo_path'] as String?, radius: 44)),
                const SizedBox(height: 8),
                if (avgRating != null)
                  Center(
                    child: Row(mainAxisSize: MainAxisSize.min, children: [
                      const Icon(Icons.star, color: Colors.amber, size: 18),
                      const SizedBox(width: 4),
                      Text('${double.parse('$avgRating').toStringAsFixed(1)} · $reviewCount reviews'),
                    ]),
                  ),
                const SizedBox(height: 4),
                const Center(
                  child: Padding(
                    padding: EdgeInsets.symmetric(horizontal: 24),
                    child: Text(
                      'Profile photo uploads are available on the HireHelper website for now.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: Colors.grey, fontSize: 12),
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                TextField(
                  controller: _bioController,
                  maxLines: 4,
                  decoration: const InputDecoration(labelText: 'About you', hintText: 'Tell customers about your experience...', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: _experienceController,
                  keyboardType: TextInputType.number,
                  decoration: const InputDecoration(labelText: 'Years of experience', border: OutlineInputBorder()),
                ),
                const SizedBox(height: 24),
                FilledButton(
                  onPressed: _saving ? null : _save,
                  style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
                  child: _saving
                      ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                      : const Text('Save Profile'),
                ),
              ],
            ),
    );
  }
}
