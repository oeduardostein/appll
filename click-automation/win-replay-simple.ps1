param(
  [Parameter(Mandatory = $true)][string]$PointsPath,
  [double]$Speed = 1.0,
  [string]$VisualDebug = "true",
  [int]$VisualDebugMs = 500,
  [int]$PreWaitMs = 5000
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
}
"@

function ToBool([string]$value, [bool]$defaultValue = $false) {
  if ([string]::IsNullOrWhiteSpace($value)) { return $defaultValue }
  $v = $value.Trim().ToLower()
  if ($v -in @("1","true","yes","y")) { return $true }
  if ($v -in @("0","false","no","n")) { return $false }
  return $defaultValue
}

function ClampInt([int]$value, [int]$min, [int]$max) {
  if ($max -lt $min) { return $min }
  if ($value -lt $min) { return $min }
  if ($value -gt $max) { return $value }
  return $value
}

function MouseDown() {
  [WinInput]::mouse_event([WinInput]::MOUSEEVENTF_LEFTDOWN,0,0,0,[UIntPtr]::Zero)
}

function MouseUp() {
  [WinInput]::mouse_event([WinInput]::MOUSEEVENTF_LEFTUP,0,0,0,[UIntPtr]::Zero)
}

function ShowDebugMarker([int]$x, [int]$y, [int]$durationMs) {
  if ($durationMs -le 0) { return }

  $dotWidth = 16
  $dotHeight = 16
  $markerColor = [System.Drawing.Color]::FromArgb(255, 255, 64, 64)
  $screen = [System.Windows.Forms.SystemInformation]::VirtualScreen
  $screenLeft = [int]$screen.Left
  $screenTop = [int]$screen.Top
  $screenRight = [int]$screen.Right
  $screenBottom = [int]$screen.Bottom
  $maxMarkerLeft = [Math]::Max($screenLeft, $screenRight - $dotWidth)
  $maxMarkerTop = [Math]::Max($screenTop, $screenBottom - $dotHeight)
  $markerLeft = ClampInt ([int]($x - [Math]::Floor($dotWidth / 2))) $screenLeft $maxMarkerLeft
  $markerTop = ClampInt ([int]($y - [Math]::Floor($dotHeight / 2))) $screenTop $maxMarkerTop

  $markerForm = New-Object System.Windows.Forms.Form
  $markerForm.AutoScaleMode = [System.Windows.Forms.AutoScaleMode]::None
  $markerForm.ClientSize = New-Object System.Drawing.Size -ArgumentList $dotWidth, $dotHeight
  $markerForm.StartPosition = [System.Windows.Forms.FormStartPosition]::Manual
  $markerForm.Location = New-Object System.Drawing.Point -ArgumentList $markerLeft, $markerTop
  $markerForm.TopMost = $true
  $markerForm.ShowInTaskbar = $false
  $markerForm.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::None
  $markerForm.BackColor = $markerColor
  $markerForm.Opacity = 0.9

  # Criar forma circular
  $path = New-Object System.Drawing.Drawing2D.GraphicsPath
  $path.AddEllipse(0, 0, $dotWidth - 1, $dotHeight - 1)
  $markerForm.Region = New-Object System.Drawing.Region($path)
  $path.Dispose()

  try {
    $markerForm.Show()
    $markerForm.Refresh()
    [System.Windows.Forms.Application]::DoEvents()
    Start-Sleep -Milliseconds $durationMs
  } finally {
    try { $markerForm.Close() } catch {}
    $markerForm.Dispose()
  }
}

function ShowDebugMarkerSafe([int]$x, [int]$y, [int]$durationMs) {
  try {
    ShowDebugMarker $x $y $durationMs
  } catch {
    # Debug visual não pode interromper o replay
  }
}

# Carregar pontos
$events = Get-Content -LiteralPath $PointsPath -Raw | ConvertFrom-Json
if ($events -isnot [System.Collections.IEnumerable]) {
  throw "Points inválido: esperado array JSON."
}

$visualDebugBool = ToBool $VisualDebug $true
$visualDebugMsSafe = [Math]::Max(0, [int]$VisualDebugMs)

Write-Host "Loaded $($events.Length) events"
Write-Host "Visual Debug: $visualDebugBool"
Write-Host "Pre-wait: ${PreWaitMs}ms"

if ($PreWaitMs -gt 0) {
  Start-Sleep -Milliseconds $PreWaitMs
}

$lastT = $null

foreach ($ev in $events) {
  $t = $null
  if ($ev.PSObject.Properties.Name -contains "t") { $t = $ev.t }

  if ($null -ne $t -and $null -ne $lastT) {
    $delta = [Math]::Max(0, [int]$t - [int]$lastT)
    if ($Speed -gt 0) {
      $scaled = [int]($delta / $Speed)
    } else {
      $scaled = $delta
    }
    Start-Sleep -Milliseconds $scaled
  }
  if ($null -ne $t) { $lastT = $t }

  $type = [string]$ev.type

  if ($type -eq "mouse_down") {
    $x = [int]$ev.x
    $y = [int]$ev.y
    [WinInput]::SetCursorPos($x, $y) | Out-Null
    MouseDown
    Write-Host "[DOWN] X=$x Y=$y"
    continue
  }

  if ($type -eq "mouse_up") {
    $x = [int]$ev.x
    $y = [int]$ev.y
    [WinInput]::SetCursorPos($x, $y) | Out-Null
    MouseUp
    Write-Host "[UP]   X=$x Y=$y"
    if ($visualDebugBool) { ShowDebugMarkerSafe $x $y $visualDebugMsSafe }
    continue
  }
}

@{ success = $true } | ConvertTo-Json -Compress
