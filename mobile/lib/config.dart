// Fill these in for your deployment, then rebuild the app.

/// Your backend's API base URL. hire.easi7.in is not advertised anywhere
/// in the Play Store listing, but it IS visible to anyone who inspects
/// this app's network traffic -- see the HireHelper build audit for what
/// that does and doesn't mean for you.
const String apiBaseUrl = 'https://hire.easi7.in/api/v1';

// Note: this app captures and sends real GPS coordinates (see
// screens/addresses_screen.dart and the location-ping calls in
// booking_detail_screen.dart) using the device's own location services --
// no Google API key needed for that. It does not render an interactive
// Google Map view (no `google_maps_flutter` dependency yet); the
// in-progress job screen shows distance as text. Adding a visual map is
// a natural next step once you have a Maps API key -- see
// mobile/README.md "Adding a visual map" for where it would plug in.

/// Category/service images and uploaded photos are returned by the API as
/// paths relative to the website's /assets/ folder (e.g.
/// "categories/plumber.svg" or "uploads/providers/xxx.png"). This turns one
/// of those into a full URL the same way the website's `asset()` helper
/// does, by swapping the API's `/api/v1` suffix for `/assets`.
String assetUrl(String relativePath) {
  final siteBase = apiBaseUrl.replaceFirst(RegExp(r'/api/v1/?$'), '');
  return '$siteBase/assets/${relativePath.replaceFirst(RegExp(r'^/'), '')}';
}
