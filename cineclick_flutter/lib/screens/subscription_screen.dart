import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../models/subscription_model.dart';
import '../providers/auth_provider.dart';
import '../services/firestore_service.dart';
import '../widgets/loading_widget.dart';

class SubscriptionScreen extends StatefulWidget {
  const SubscriptionScreen({super.key});

  @override
  State<SubscriptionScreen> createState() => _SubscriptionScreenState();
}

class _SubscriptionScreenState extends State<SubscriptionScreen> {
  final FirestoreService _firestoreService = FirestoreService();

  static const _plans = [
    {'name': 'Basic', 'price': '\$5/mo', 'features': ['HD streaming', '1 device'], 'months': 1},
    {'name': 'Standard', 'price': '\$10/mo', 'features': ['Full HD', '2 devices', 'Downloads'], 'months': 1},
    {'name': 'Premium', 'price': '\$15/mo', 'features': ['4K Ultra HD', '4 devices', 'Downloads', 'Early access'], 'months': 1},
  ];

  String? _selectedPlan;
  bool _isProcessing = false;

  Future<void> _subscribe() async {
    if (_selectedPlan == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select a plan')),
      );
      return;
    }
    final auth = context.read<AppAuthProvider>();
    if (auth.user == null) return;

    setState(() => _isProcessing = true);
    try {
      final subscription = SubscriptionModel(
        userId: auth.user!.id,
        plan: _selectedPlan!,
        expiryDate: DateTime.now().add(const Duration(days: 30)),
      );
      await _firestoreService.addSubscription(subscription);
      await auth.refreshUser();

      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('$_selectedPlan plan activated! Enjoy streaming.'),
            backgroundColor: Colors.green,
          ),
        );
        Navigator.pop(context);
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Subscription failed: $e'),
            backgroundColor: Theme.of(context).colorScheme.error,
          ),
        );
      }
    } finally {
      if (mounted) setState(() => _isProcessing = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(title: const Text('Choose a Plan')),
      body: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Unlock unlimited streaming',
              style: theme.textTheme.titleLarge
                  ?.copyWith(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 6),
            const Text(
              'All plans include a 7-day free trial. Cancel anytime.',
              style: TextStyle(color: Colors.white54, fontSize: 13),
            ),
            const SizedBox(height: 24),
            Expanded(
              child: ListView.separated(
                itemCount: _plans.length,
                separatorBuilder: (_, __) => const SizedBox(height: 12),
                itemBuilder: (_, i) {
                  final plan = _plans[i];
                  final name = plan['name'] as String;
                  final isSelected = _selectedPlan == name;
                  return _PlanCard(
                    name: name,
                    price: plan['price'] as String,
                    features: plan['features'] as List<String>,
                    isSelected: isSelected,
                    onTap: () => setState(() => _selectedPlan = name),
                    isHighlighted: i == 1,
                  );
                },
              ),
            ),
            const SizedBox(height: 16),
            _isProcessing
                ? const LoadingWidget(size: 40)
                : SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: _subscribe,
                      style: ElevatedButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 14),
                      ),
                      child: const Text(
                        'Subscribe Now',
                        style: TextStyle(
                            fontSize: 16, fontWeight: FontWeight.bold),
                      ),
                    ),
                  ),
            const SizedBox(height: 8),
            const Center(
              child: Text(
                'Secure mock checkout — no real charges',
                style: TextStyle(color: Colors.white38, fontSize: 11),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PlanCard extends StatelessWidget {
  final String name;
  final String price;
  final List<String> features;
  final bool isSelected;
  final bool isHighlighted;
  final VoidCallback onTap;

  const _PlanCard({
    required this.name,
    required this.price,
    required this.features,
    required this.isSelected,
    required this.isHighlighted,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final borderColor =
        isSelected ? theme.colorScheme.primary : Colors.white12;
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        decoration: BoxDecoration(
          color: isSelected
              ? theme.colorScheme.primary.withValues(alpha: 0.15)
              : Colors.white.withValues(alpha: 0.05),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(
              color: borderColor, width: isSelected ? 2 : 1),
        ),
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Text(
                        name,
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 16,
                          color: isSelected
                              ? theme.colorScheme.primary
                              : Colors.white,
                        ),
                      ),
                      if (isHighlighted) ...[
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: Colors.amber,
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: const Text(
                            'Popular',
                            style: TextStyle(
                                fontSize: 10,
                                color: Colors.black,
                                fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ],
                  ),
                  const SizedBox(height: 4),
                  Text(
                    price,
                    style: TextStyle(
                      color: isSelected
                          ? theme.colorScheme.primary
                          : Colors.white70,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                  const SizedBox(height: 8),
                  ...features.map((f) => Padding(
                        padding: const EdgeInsets.only(bottom: 2),
                        child: Row(
                          children: [
                            const Icon(Icons.check, size: 14,
                                color: Colors.greenAccent),
                            const SizedBox(width: 6),
                            Text(f,
                                style: const TextStyle(
                                    fontSize: 12, color: Colors.white70)),
                          ],
                        ),
                      )),
                ],
              ),
            ),
            Icon(
              isSelected
                  ? Icons.radio_button_checked
                  : Icons.radio_button_off,
              color: isSelected
                  ? theme.colorScheme.primary
                  : Colors.white30,
            ),
          ],
        ),
      ),
    );
  }
}
