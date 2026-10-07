import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../api_client.dart';
import '../widgets.dart';

class WalletScreen extends StatelessWidget {
  const WalletScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Wallet & Referrals')),
      body: AsyncScreen<Map<String, dynamic>>(
        load: () => ApiClient().get('/wallet'),
        builder: (context, data, reload) {
          final balance = double.parse('${data['balance']}');
          final referralCode = data['referral_code'] as String? ?? '';
          final history = List<Map<String, dynamic>>.from(data['history'] as List);
          return RefreshIndicator(
            onRefresh: () async => reload(),
            child: ListView(
              padding: const EdgeInsets.all(20),
              children: [
                Container(
                  padding: const EdgeInsets.all(24),
                  decoration: BoxDecoration(
                    gradient: const LinearGradient(colors: [primaryColor, accentColor]),
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Wallet balance', style: TextStyle(color: Colors.white70)),
                      const SizedBox(height: 8),
                      Text(formatMoney(balance), style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                Card(
                  child: Padding(
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text('Your referral code', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 4),
                        const Text('Share this with friends. You both get a wallet bonus when they sign up.', style: TextStyle(color: Colors.grey, fontSize: 13)),
                        const SizedBox(height: 12),
                        Row(children: [
                          Expanded(
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                              decoration: BoxDecoration(color: Colors.grey.shade100, borderRadius: BorderRadius.circular(8)),
                              child: Text(referralCode, style: const TextStyle(fontWeight: FontWeight.bold, letterSpacing: 2)),
                            ),
                          ),
                          const SizedBox(width: 8),
                          IconButton(
                            icon: const Icon(Icons.copy, color: primaryColor),
                            onPressed: () {
                              Clipboard.setData(ClipboardData(text: referralCode));
                              showSuccess(context, 'Referral code copied.');
                            },
                          ),
                        ]),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 20),
                const Text('Transaction history', style: TextStyle(fontWeight: FontWeight.bold)),
                const SizedBox(height: 8),
                if (history.isEmpty)
                  const Padding(padding: EdgeInsets.all(24), child: Center(child: Text('No wallet activity yet.')))
                else
                  ...history.map((h) {
                    final amount = double.parse('${h['amount']}');
                    final isCredit = amount >= 0;
                    return Card(
                      margin: const EdgeInsets.only(bottom: 8),
                      child: ListTile(
                        leading: Icon(isCredit ? Icons.add_circle_outline : Icons.remove_circle_outline, color: isCredit ? Colors.green : Colors.red),
                        title: Text(h['reason'] as String? ?? ''),
                        subtitle: Text('${h['created_at']}'),
                        trailing: Text(
                          '${isCredit ? '+' : ''}${formatMoney(amount)}',
                          style: TextStyle(fontWeight: FontWeight.bold, color: isCredit ? Colors.green.shade700 : Colors.red.shade700),
                        ),
                      ),
                    );
                  }),
              ],
            ),
          );
        },
      ),
    );
  }
}
