import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'config.dart';

class ApiException implements Exception {
  final String message;
  final int statusCode;
  ApiException(this.message, this.statusCode);

  @override
  String toString() => message;
}

/// Thin wrapper around the HireHelper JSON API. Holds the bearer token in
/// shared_preferences once logged in; every call attaches it automatically
/// when present. Mirrors the website's App\Core\ApiAuth on the server.
/// `ApiClient()` always returns the same shared instance -- the token is
/// loaded once at splash and kept in memory from then on, so every screen
/// that writes `ApiClient()` is already using the logged-in session
/// without needing to thread an instance through the widget tree.
class ApiClient {
  ApiClient._internal();
  static final ApiClient _instance = ApiClient._internal();
  factory ApiClient() => _instance;

  static const _tokenKey = 'hirehelper_token';
  static const _roleKey = 'hirehelper_role';
  static const _nameKey = 'hirehelper_name';
  static const _userIdKey = 'hirehelper_user_id';

  String? _token;

  Future<void> loadToken() async {
    final prefs = await SharedPreferences.getInstance();
    _token = prefs.getString(_tokenKey);
  }

  bool get isLoggedIn => _token != null;

  Future<void> saveSession(Map<String, dynamic> user, String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
    await prefs.setString(_roleKey, user['role'] as String);
    await prefs.setString(_nameKey, user['name'] as String);
    await prefs.setInt(_userIdKey, user['id'] as int);
    _token = token;
  }

  Future<String?> getRole() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_roleKey);
  }

  Future<String?> getName() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_nameKey);
  }

  Future<void> clearSession() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_tokenKey);
    await prefs.remove(_roleKey);
    await prefs.remove(_nameKey);
    await prefs.remove(_userIdKey);
    _token = null;
  }

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        if (_token != null) 'Authorization': 'Bearer $_token',
      };

  Future<Map<String, dynamic>> get(String path) async {
    final res = await http.get(Uri.parse('$apiBaseUrl$path'), headers: _headers);
    return _decode(res);
  }

  Future<Map<String, dynamic>> post(String path, [Map<String, dynamic>? body]) async {
    final res = await http.post(
      Uri.parse('$apiBaseUrl$path'),
      headers: _headers,
      body: jsonEncode(body ?? {}),
    );
    return _decode(res);
  }

  Map<String, dynamic> _decode(http.Response res) {
    Map<String, dynamic> data = {};
    if (res.body.isNotEmpty) {
      final decoded = jsonDecode(res.body);
      if (decoded is Map<String, dynamic>) data = decoded;
    }
    if (res.statusCode >= 200 && res.statusCode < 300) {
      return data;
    }
    throw ApiException(data['error'] as String? ?? 'Something went wrong (${res.statusCode}).', res.statusCode);
  }
}
