import 'package:flutter/material.dart';
import '../../api_client.dart';
import '../../widgets.dart';
import 'service_detail_screen.dart';

class CategoryScreen extends StatelessWidget {
  final Map<String, dynamic> category;
  const CategoryScreen({super.key, required this.category});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text(category['name'] as String)),
      body: AsyncScreen<List<Map<String, dynamic>>>(
        load: () async {
          final result = await ApiClient().get('/categories/${category['slug']}/services');
          return List<Map<String, dynamic>>.from(result['services'] as List);
        },
        builder: (context, services, reload) {
          if (services.isEmpty) {
            return const Center(child: Text('No services under this category yet.'));
          }
          return RefreshIndicator(
            onRefresh: () async => reload(),
            child: ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: services.length,
              separatorBuilder: (_, __) => const SizedBox(height: 12),
              itemBuilder: (context, i) {
                final service = services[i];
                final imagePath = (service['image_path'] as String?) ?? (category['image_path'] as String?);
                return Card(
                  child: ListTile(
                    contentPadding: const EdgeInsets.all(16),
                    leading: SizedBox(
                      width: 48,
                      height: 48,
                      child: ClipRRect(
                        borderRadius: BorderRadius.circular(8),
                        child: RemoteThumb(
                          path: imagePath,
                          fallback: Container(
                            color: primaryColor.withValues(alpha: 0.08),
                            child: const Icon(Icons.home_repair_service_outlined, color: primaryColor),
                          ),
                        ),
                      ),
                    ),
                    title: Text(service['name'] as String, style: const TextStyle(fontWeight: FontWeight.w600)),
                    subtitle: Text('${service['duration_minutes']} mins'),
                    trailing: Text(formatMoney(double.parse('${service['price']}')), style: const TextStyle(fontWeight: FontWeight.bold, color: primaryColor)),
                    onTap: () => Navigator.of(context).push(
                      MaterialPageRoute(builder: (_) => ServiceDetailScreen(slug: service['slug'] as String)),
                    ),
                  ),
                );
              },
            ),
          );
        },
      ),
    );
  }
}
