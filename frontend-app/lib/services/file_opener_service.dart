import 'dart:io';

import 'package:flutter/services.dart';

class FileOpenerService {
  static const MethodChannel _channel =
      MethodChannel('br.com.appgestaoll/file_opener');

  FileOpenerService._();

  static Future<bool> openFile(
    String path, {
    String mimeType = '*/*',
  }) async {
    if (!Platform.isAndroid && !Platform.isIOS) {
      return false;
    }

    final args = <String, dynamic>{
      'path': path,
      'mimeType': mimeType,
    };

    try {
      final result = await _channel.invokeMethod<bool>('openFile', args);
      return result ?? false;
    } on PlatformException {
      return false;
    }
  }
}
