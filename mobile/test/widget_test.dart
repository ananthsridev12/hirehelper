import 'package:flutter_test/flutter_test.dart';

import 'package:hirehelper/main.dart';

void main() {
  testWidgets('App boots to the splash screen', (WidgetTester tester) async {
    await tester.pumpWidget(const HireHelperApp());

    // Splash shows immediately; it then calls the (unreachable, in a
    // test) API to decide where to route, so we only assert the first
    // frame here rather than waiting for that network call to settle.
    expect(find.text('HireHelper'), findsOneWidget);
  });
}
