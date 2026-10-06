# HireHelper mobile app

A single Flutter app for both customers and professionals -- the home
screen branches by role after login, the same way the website does.
Talks to the JSON API under `/api/v1` on the same backend as the website
(`app/Controllers/Api/*` in the repo root) -- same database, same
business rules, just a different front door with token auth instead of
browser sessions.

**Verification status**: this was built in an environment with no
Android SDK or Xcode, so a real device/emulator build and run hasn't
happened. What *has* been verified, with the actual Flutter 3.27 stable
SDK: `flutter pub get` resolves every dependency cleanly, `flutter
analyze` reports zero issues, and `flutter test` passes. The native
`android/` and `ios/` project folders here are the real output of
`flutter create`, already generated and committed -- you don't need to
run that yourself. What's unverified is specifically the native build
step (Gradle/Android, CocoaPods/iOS) and anything that only shows up at
runtime on a device.

## One-time setup

```bash
# 1. Install the Flutter SDK if you haven't: https://flutter.dev/docs/get-started/install
flutter --version

# 2. Install dependencies
cd mobile
flutter pub get

# 3. Confirm your SDK/toolchain agrees with what's here
flutter analyze
flutter test

# 4. The real test: run it for real, on an emulator/device or for web
flutter run
```

## Point it at your backend

Edit `lib/config.dart`:

```dart
const String apiBaseUrl = 'https://hire.easi7.in/api/v1';
```

This is already set to your deployed backend. Change it only if the
domain changes, or to `http://10.0.2.2:8000/api/v1` for an Android
emulator talking to a `php -S` instance on your own machine (`10.0.2.2`
is the emulator's alias for your computer's `localhost`).

## Location permission

Already wired in on both platforms -- `android/app/src/main/AndroidManifest.xml`
has `ACCESS_FINE_LOCATION`, `ACCESS_COARSE_LOCATION` and `INTERNET`, and
`ios/Runner/Info.plist` has `NSLocationWhenInUseUsageDescription`. Nothing
to do here unless you want to change the permission rationale text shown
to iOS users.

## Adding a visual map

The app captures and transmits real GPS coordinates today (see
`lib/screens/addresses_screen.dart`'s "Use my current GPS location"
button and the periodic location-ping calls in
`lib/screens/customer/booking_detail_screen.dart`) -- no Google API key
needed for that, it's the device's own location services. What it
doesn't do yet is render an actual map image with pins; the in-progress
job screen shows distance as plain text instead.

To add that: add `google_maps_flutter` to `pubspec.yaml`, get a Google
Cloud API key with the Maps SDK for Android/iOS enabled (billing on),
add it to `android/app/src/main/AndroidManifest.xml` as
`<meta-data android:name="com.google.android.geo.API_KEY" android:value="YOUR_KEY"/>`
inside `<application>` (and the iOS equivalent in `AppDelegate`), then
drop a `GoogleMap` widget into the "Job in progress" section of
`booking_detail_screen.dart` using the address's `lat`/`lng` and the
`distance_km` response already being fetched there.

## Building a release

```bash
flutter build appbundle --release
```

Before uploading to Play Console, you still need, separately from this
app:

- **A signing key** (`android/key.properties` + a `.jks` keystore --
  `flutter build` won't make one for you; see Flutter's own
  ["Build and release" guide](https://docs.flutter.dev/deployment/android)).
- **A Play Console developer account** (one-time $25 fee, Google's own
  signup flow).
- **App icon and screenshots** for the store listing.
- **Privacy Policy and Terms URLs** -- the website already has these at
  `/privacy` and `/terms` (e.g. `https://hire.easi7.in/privacy`); Play
  Console asks for the Privacy Policy URL specifically during setup.
  Edit the bracketed placeholders in `app/Views/pages/privacy.php` and
  `terms.php` in the repo root with your real business details first.

None of those four are something this repo or I can do for you --
they require your own Google account, business details, and design
assets.

## Push notifications (not wired up yet)

The in-app notification feed (bell icon, `/api/v1/notifications`) works
today regardless of this. OS-level push (a notification while the app is
closed) needs a Firebase project -- see `app/Core/Notifier.php` in the
repo root for the exact seam where that plugs in once you create one, and
`app/Controllers/Api/NotificationController::registerDeviceToken` for
where this app would register its push token (call it once after login
with the token from the `firebase_messaging` package once you add it).

## Folder structure

```
lib/
  config.dart          API base URL
  api_client.dart       Bearer-token HTTP client (singleton, holds the
                         logged-in session in memory + shared_preferences)
  widgets.dart          Shared bits: status badges, loading/error wrapper
  main.dart             Splash screen, routes to login or home by role
  screens/
    login_screen.dart, register_screen.dart
    addresses_screen.dart       GPS capture + manual entry, shared by both roles
    notifications_screen.dart   Shared by both roles
    customer/            Browse categories, book, track, review
    provider/            Job list, availability toggle
```
