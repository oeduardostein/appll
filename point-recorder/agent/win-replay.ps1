param(
  [Parameter(Mandatory = $true)][string]$TemplatePath,
  [Parameter(Mandatory = $true)][string]$DataPath,
  [Parameter(Mandatory = $true)][string]$ScreenshotsDir,
  [int]$MaxDelayMs = 5000,
  [double]$Speed = 1.0,
  [string]$ReplayText = "false"
)

$ErrorActionPreference = "Stop"

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

Add-Type @"
using System;
using System.Runtime.InteropServices;
public static class WinInput {
  [DllImport("user32.dll")]
  public static extern bool SetCursorPos(int X, int Y);

  [DllImport("user32.dll")]
  public static extern void mouse_event(uint dwFlags, uint dx, uint dy, uint dwData, UIntPtr dwExtraInfo);

  public const uint MOUSEEVENTF_LEFTDOWN  = 0x0002;
  public const uint MOUSEEVENTF_LEFTUP    = 0x0004;
  public const uint MOUSEEVENTF_RIGHTDOWN = 0x0008;
  public const uint MOUSEEVENTF_RIGHTUP   = 0x0010;
  public const uint MOUSEEVENTF_MIDDLEDOWN= 0x0020;
  public const uint MOUSEEVENTF_MIDDLEUP  = 0x0040;
}
"@

function ToBool([string]$value, [bool]$defaultValue = $false) {
  if ([string]::IsNullOrWhiteSpace($value)) { return $defaultValue }
  $v = $value.Trim().ToLower()
  if ($v -in @("1","true","yes","y")) { return $true }
  if ($v -in @("0","false","no","n")) { return $false }
  return $defaultValue
}

function SleepMs([int]$ms) {
  if ($ms -le 0) { return }
  Start-Sleep -Milliseconds $ms
}

function EnsureDir([string]$dir) {
  if (-not (Test-Path -LiteralPath $dir)) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
  }
}

function MapButton([int]$btn) {
  switch ($btn) {
    2 { return "right" }
    3 { return "middle" }
    Default { return "left" }
  }
}

function MouseDown([string]$button) {
  switch ($button) {
    "right"  { [WinInput]::mouse_event([WinInput]::MOUSEEVENTF_RIGHTDOWN,0,0,0,[UIntPtr]::Zero) }
    "middle" { [WinInput]::mouse_event([WinInput]::MOUSEEVENTF_MIDDLEDOWN,0,0,0,[UIntPtr]::Zero) }
    Default  { [WinInput]::mouse_event([WinInput]::MOUSEEVENTF_LEFTDOWN,0,0,0,[UIntPtr]::Zero) }
  }
}

function MouseUp([string]$button) {
  switch ($button) {
    "right"  { [WinInput]::mouse_event([WinInput]::MOUSEEVENTF_RIGHTUP,0,0,0,[UIntPtr]::Zero) }
    "middle" { [WinInput]::mouse_event([WinInput]::MOUSEEVENTF_MIDDLEUP,0,0,0,[UIntPtr]::Zero) }
    Default  { [WinInput]::mouse_event([WinInput]::MOUSEEVENTF_LEFTUP,0,0,0,[UIntPtr]::Zero) }
  }
}

function MapSpecialKey($keycode, $rawcode) {
  $candidates = @()
  if ($null -ne $keycode) { $candidates += [int]$keycode }
  if ($null -ne $rawcode) { $candidates += [int]$rawcode }

  foreach ($code in $candidates) {
    if ($code -eq 28 -or $code -eq 13) { return "{ENTER}" }
    if ($code -eq 15 -or $code -eq 9) { return "{TAB}" }
    if ($code -eq 1  -or $code -eq 27) { return "{ESC}" }
    if ($code -eq 14 -or $code -eq 8) { return "{BACKSPACE}" }
    if ($code -eq 57 -or $code -eq 32) { return " " }

    if ($code -eq 57416 -or $code -eq 38) { return "{UP}" }
    if ($code -eq 57424 -or $code -eq 40) { return "{DOWN}" }
    if ($code -eq 57419 -or $code -eq 37) { return "{LEFT}" }
    if ($code -eq 57421 -or $code -eq 39) { return "{RIGHT}" }
  }
  return $null
}

function EscapeSendKeys([string]$text) {
  if ($null -eq $text) { return "" }
  $t = $text.Replace("{","{{}").Replace("}","{}}")
  $t = $t.Replace("+","{+}").Replace("^","{^}").Replace("%","{%}")
  return $t
}

function PasteText([string]$text) {
  $safe = [string]::IsNullOrEmpty($text) ? "" : $text
  [System.Windows.Forms.Clipboard]::SetText($safe)
  [System.Windows.Forms.SendKeys]::SendWait("^v")
}

function TakeScreenshot([string]$dir) {
  EnsureDir $dir
  $file = Join-Path $dir ("shot_" + [DateTime]::UtcNow.ToString("yyyyMMdd_HHmmss_fff") + ".png")

  $bounds = [System.Windows.Forms.Screen]::PrimaryScreen.Bounds
  $bmp = New-Object System.Drawing.Bitmap $bounds.Width, $bounds.Height
  $graphics = [System.Drawing.Graphics]::FromImage($bmp)
  $graphics.CopyFromScreen($bounds.X, $bounds.Y, 0, 0, $bounds.Size)
  $bmp.Save($file, [System.Drawing.Imaging.ImageFormat]::Png)
  $graphics.Dispose()
  $bmp.Dispose()

  return $file
}

$events = Get-Content -LiteralPath $TemplatePath -Raw | ConvertFrom-Json
if ($events -isnot [System.Collections.IEnumerable]) {
  throw "Template inválido: esperado array JSON."
}

$data = Get-Content -LiteralPath $DataPath -Raw | ConvertFrom-Json
$replayTextBool = ToBool $ReplayText $false

EnsureDir $ScreenshotsDir

$lastT = $null
$slotActive = $null
$lastScreenshot = $null

foreach ($ev in $events) {
  $t = $null
  if ($ev.PSObject.Properties.Name -contains "t") { $t = $ev.t }

  if ($null -ne $t -and $null -ne $lastT) {
    $delta = [Math]::Max(0, [int]$t - [int]$lastT)
    $scaled = $Speed -gt 0 ? [int]($delta / $Speed) : $delta
    SleepMs ([Math]::Min($MaxDelayMs, $scaled))
  }
  if ($null -ne $t) { $lastT = $t }

  $type = [string]$ev.type

  if ($type -eq "slot_begin") {
    $name = [string]$ev.name
    if (-not [string]::IsNullOrWhiteSpace($name)) {
      $slotActive = $name
      $value = $data.$name
      if ($null -eq $value) { $value = "" }
      PasteText ([string]$value)
    }
    continue
  }

  if ($type -eq "screenshot") {
    $lastScreenshot = TakeScreenshot $ScreenshotsDir
    continue
  }

  if ($type -eq "mouse_down") {
    $slotActive = $null
    $x = [int]$ev.x
    $y = [int]$ev.y
    [WinInput]::SetCursorPos($x, $y) | Out-Null
    MouseDown (MapButton([int]$ev.button))
    continue
  }

  if ($type -eq "mouse_up") {
    $x = [int]$ev.x
    $y = [int]$ev.y
    [WinInput]::SetCursorPos($x, $y) | Out-Null
    MouseUp (MapButton([int]$ev.button))
    continue
  }

  if ($type -eq "key_down" -or $type -eq "key_up") {
    $mapped = MapSpecialKey $ev.keycode $ev.rawcode
    if ($null -eq $mapped) { continue }

    if ($slotActive) {
      if ($mapped -ne "{TAB}" -and $mapped -ne "{ENTER}" -and $mapped -ne "{ESC}") {
        continue
      }
    }

    if ($type -eq "key_down") {
      [System.Windows.Forms.SendKeys]::SendWait($mapped)
    } else {
      # key_up não é necessário com SendKeys
    }

    if ($slotActive -and ($mapped -eq "{TAB}" -or $mapped -eq "{ENTER}" -or $mapped -eq "{ESC}")) {
      $slotActive = $null
    }
    continue
  }

  if ($type -eq "key_press" -and $replayTextBool) {
    if ($slotActive) { continue }
    $char = [string]$ev.char
    if ([string]::IsNullOrEmpty($char)) { continue }
    if ($char.Length -eq 1) {
      [System.Windows.Forms.SendKeys]::SendWait((EscapeSendKeys $char))
    }
    continue
  }
}

@{
  lastScreenshotPath = $lastScreenshot
} | ConvertTo-Json -Compress
