import Flutter
import UIKit

private let fileOpenerChannelName = "br.com.appgestaoll/file_opener"

@main
@objc class AppDelegate: FlutterAppDelegate, UIDocumentInteractionControllerDelegate {
  private var documentController: UIDocumentInteractionController?

  override func application(
    _ application: UIApplication,
    didFinishLaunchingWithOptions launchOptions: [UIApplication.LaunchOptionsKey: Any]?
  ) -> Bool {
    GeneratedPluginRegistrant.register(with: self)

    if let controller = window?.rootViewController as? FlutterViewController {
      let fileOpenerChannel = FlutterMethodChannel(
        name: fileOpenerChannelName,
        binaryMessenger: controller.binaryMessenger
      )

      fileOpenerChannel.setMethodCallHandler { [weak self] call, result in
        if call.method == "openFile" {
          self?.handleOpenFile(call, result: result)
        } else {
          result(FlutterMethodNotImplemented)
        }
      }
    }

    return super.application(application, didFinishLaunchingWithOptions: launchOptions)
  }

  private func handleOpenFile(_ call: FlutterMethodCall, result: @escaping FlutterResult) {
    guard
      let args = call.arguments as? [String: Any],
      let path = args["path"] as? String
    else {
      result(FlutterMethodNotImplemented)
      return
    }

    guard FileManager.default.fileExists(atPath: path) else {
      result(false)
      return
    }

    let fileURL = URL(fileURLWithPath: path)

    DispatchQueue.main.async { [weak self] in
      guard let self = self, let root = self.window?.rootViewController else {
        result(false)
        return
      }

      self.documentController = UIDocumentInteractionController(url: fileURL)
      self.documentController?.delegate = self

      if self.documentController?.presentPreview(animated: true) == true {
        result(true)
        return
      }

      if self.documentController?.presentOpenInMenu(
        from: root.view.bounds,
        in: root.view,
        animated: true
      ) == true {
        result(true)
        return
      }

      result(false)
    }
  }

  func documentInteractionControllerViewControllerForPreview(
    _ controller: UIDocumentInteractionController
  ) -> UIViewController {
    return window?.rootViewController ?? UIViewController()
  }
}
