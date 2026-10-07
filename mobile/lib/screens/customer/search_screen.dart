import 'dart:async';
import 'package:flutter/material.dart';
import '../../api_client.dart';
import '../../widgets.dart';
import 'service_detail_screen.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final _controller = TextEditingController();
  Timer? _debounce;
  List<Map<String, dynamic>> _results = [];
  bool _loading = false;
  bool _searched = false;

  @override
  void dispose() {
    _debounce?.cancel();
    _controller.dispose();
    super.dispose();
  }

  void _onChanged(String query) {
    _debounce?.cancel();
    _debounce = Timer(const Duration(milliseconds: 400), () => _search(query));
  }

  Future<void> _search(String query) async {
    final q = query.trim();
    if (q.isEmpty) {
      setState(() {
        _results = [];
        _searched = false;
      });
      return;
    }
    setState(() => _loading = true);
    try {
      final result = await ApiClient().get('/search?q=${Uri.encodeQueryComponent(q)}');
      setState(() {
        _results = List<Map<String, dynamic>>.from(result['results'] as List);
        _searched = true;
      });
    } catch (e) {
      if (mounted) showError(context, e);
    } finally {
      if (mounted) setState(() => _loading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: TextField(
          controller: _controller,
          autofocus: true,
          onChanged: _onChanged,
          decoration: const InputDecoration(
            hintText: 'Search for a service...',
            border: InputBorder.none,
          ),
        ),
      ),
      body: _loading
          ? const Center(child: CircularProgressIndicator())
          : !_searched
              ? const Center(child: Text('Try "AC repair", "cleaning", "salon"...'))
              : _results.isEmpty
                  ? const Center(child: Text('No services matched your search.'))
                  : ListView.separated(
                      padding: const EdgeInsets.all(16),
                      itemCount: _results.length,
                      separatorBuilder: (_, __) => const SizedBox(height: 12),
                      itemBuilder: (context, i) {
                        final service = _results[i];
                        return Card(
                          child: ListTile(
                            contentPadding: const EdgeInsets.all(16),
                            leading: SizedBox(
                              width: 48,
                              height: 48,
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(8),
                                child: RemoteThumb(
                                  path: service['image_path'] as String?,
                                  fallback: Container(
                                    color: primaryColor.withValues(alpha: 0.08),
                                    child: const Icon(Icons.home_repair_service_outlined, color: primaryColor),
                                  ),
                                ),
                              ),
                            ),
                            title: Text(service['name'] as String, style: const TextStyle(fontWeight: FontWeight.w600)),
                            subtitle: Text(service['category_name'] as String? ?? ''),
                            trailing: Text(formatMoney(double.parse('${service['price']}')), style: const TextStyle(fontWeight: FontWeight.bold, color: primaryColor)),
                            onTap: () => Navigator.of(context).push(
                              MaterialPageRoute(builder: (_) => ServiceDetailScreen(slug: service['slug'] as String)),
                            ),
                          ),
                        );
                      },
                    ),
    );
  }
}
