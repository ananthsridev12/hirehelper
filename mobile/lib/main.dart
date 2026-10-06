import 'package:flutter/material.dart';
import 'api_client.dart';
import 'widgets.dart';
import 'screens/login_screen.dart';
import 'screens/customer/customer_home_screen.dart';
import 'screens/provider/provider_jobs_screen.dart';

void main() {
  runApp(const HireHelperApp());
}

class HireHelperApp extends StatelessWidget {
  const HireHelperApp({super.key});

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      title: 'HireHelper',
      debugShowCheckedModeBanner: false,
      theme: ThemeData(
        useMaterial3: true,
        colorSchemeSeed: primaryColor,
        appBarTheme: const AppBarTheme(backgroundColor: Colors.white, foregroundColor: Colors.black, elevation: 0.5),
      ),
      home: const SplashScreen(),
    );
  }
}

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _decide();
  }

  Future<void> _decide() async {
    final api = ApiClient();
    await api.loadToken();

    if (!api.isLoggedIn) {
      _goTo(const LoginScreen());
      return;
    }

    try {
      final result = await api.get('/me');
      final role = result['user']['role'] as String;
      if (role == 'provider') {
        _goTo(const ProviderJobsScreen());
      } else {
        _goTo(const CustomerHomeScreen());
      }
    } catch (_) {
      // Token expired/revoked server-side -- back to login.
      await api.clearSession();
      _goTo(const LoginScreen());
    }
  }

  void _goTo(Widget screen) {
    if (!mounted) return;
    Navigator.of(context).pushReplacement(MaterialPageRoute(builder: (_) => screen));
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: primaryColor,
      body: Center(
        child: Text(
          'HireHelper',
          style: TextStyle(color: Colors.white, fontSize: 28, fontWeight: FontWeight.bold),
        ),
      ),
    );
  }
}
