import 'package:flutter/material.dart';
import '../api_client.dart';
import '../widgets.dart';

class SupportScreen extends StatefulWidget {
  const SupportScreen({super.key});

  @override
  State<SupportScreen> createState() => _SupportScreenState();
}

class _SupportScreenState extends State<SupportScreen> {
  final _subjectController = TextEditingController();
  final _messageController = TextEditingController();
  bool _submitting = false;

  Future<void> _submit() async {
    if (_subjectController.text.trim().isEmpty || _messageController.text.trim().isEmpty) {
      showError(context, 'Please fill in both the subject and message.');
      return;
    }
    setState(() => _submitting = true);
    try {
      await ApiClient().post('/support', {
        'subject': _subjectController.text.trim(),
        'message': _messageController.text.trim(),
      });
      if (!mounted) return;
      showSuccess(context, "Thanks for reaching out -- we'll get back to you soon.");
      Navigator.of(context).pop();
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Help & Support')),
      body: ListView(
        padding: const EdgeInsets.all(20),
        children: [
          const Text('Contact support', style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          const Text('Have a question or an issue not tied to a specific booking? Send us a message.', style: TextStyle(color: Colors.grey)),
          const SizedBox(height: 20),
          TextField(controller: _subjectController, decoration: const InputDecoration(labelText: 'Subject', border: OutlineInputBorder())),
          const SizedBox(height: 16),
          TextField(
            controller: _messageController,
            maxLines: 5,
            decoration: const InputDecoration(labelText: 'Message', border: OutlineInputBorder()),
          ),
          const SizedBox(height: 24),
          FilledButton(
            onPressed: _submitting ? null : _submit,
            style: FilledButton.styleFrom(padding: const EdgeInsets.symmetric(vertical: 16)),
            child: _submitting
                ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Text('Send message'),
          ),
        ],
      ),
    );
  }
}
