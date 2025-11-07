import 'dart:convert';

import 'package:pdf/pdf.dart';
import 'package:pdf/widgets.dart' as pw;
import 'package:share_plus/share_plus.dart';

class PdfShareHelper {
  static const _jsonEncoder = JsonEncoder.withIndent('  ');

  static Future<void> share({
    required String title,
    required String filenamePrefix,
    required Map<String, dynamic> data,
    String? subtitle,
  }) async {
    final doc = pw.Document();
    final pretty = _jsonEncoder.convert(data);
    final monoFont = pw.Font.courier();
    final trimmedSubtitle = subtitle?.trim();

    doc.addPage(
      pw.MultiPage(
        pageFormat: PdfPageFormat.a4,
        margin: const pw.EdgeInsets.fromLTRB(32, 32, 32, 40),
        build: (context) => [
          pw.Text(
            title,
            style: pw.TextStyle(
              fontSize: 18,
              fontWeight: pw.FontWeight.bold,
              color: PdfColors.blue900,
            ),
          ),
          if (trimmedSubtitle != null && trimmedSubtitle.isNotEmpty) ...[
            pw.SizedBox(height: 4),
            pw.Text(
              trimmedSubtitle,
              style: const pw.TextStyle(
                fontSize: 12,
                color: PdfColors.blueGrey700,
              ),
            ),
          ],
          pw.SizedBox(height: 12),
          pw.Container(
            padding: const pw.EdgeInsets.all(12),
            decoration: pw.BoxDecoration(
              color: PdfColors.grey100,
              borderRadius: pw.BorderRadius.circular(10),
              border: pw.Border.all(color: PdfColors.blueGrey200, width: 0.4),
            ),
            child: pw.Paragraph(
              text: pretty,
              style: pw.TextStyle(
                font: monoFont,
                fontSize: 10,
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );

    final bytes = await doc.save();
    final filename =
        '${filenamePrefix}_${DateTime.now().millisecondsSinceEpoch}.pdf';

    await Share.shareXFiles(
      [
        XFile.fromData(
          bytes,
          mimeType: 'application/pdf',
          name: filename,
        ),
      ],
      text: title,
    );
  }
}
