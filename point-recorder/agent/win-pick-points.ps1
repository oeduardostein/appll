param(
  [Parameter(Mandatory = $true)][string]$OutputPath,
  [int]$CardMs = 1200,
  [int]$PollMs = 20
)

$ErrorActionPreference = "Stop"

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

Add-Type @"
using System;
using System.Runtime.InteropServices;
public static class NativeInput {
  [StructLayout(LayoutKind.Sequential)]
  public struct POINT {
    public int X;
    public int Y;
  }

  [DllImport("user32.dll")]
  public static extern short GetAsyncKeyState(int vKey);

  [DllImport("user32.dll")]
  public static extern bool GetCursorPos(out POINT lpPoint);
}
"@

function EnsureDir([string]$dir) {
  if (-not (Test-Path -LiteralPath $dir)) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
  }
}

function Write-Utf8NoBom([string]$path, [string]$content) {
  $encoding = New-Object System.Text.UTF8Encoding($false)
  [System.IO.File]::WriteAllText($path, $content, $encoding)
}

function ClampInt([int]$value, [int]$min, [int]$max) {
  if ($max -lt $min) { return $min }
  if ($value -lt $min) { return $min }
  if ($value -gt $max) { return $max }
  return $value
}

function IsKeyDown([int]$vk) {
  return (([NativeInput]::GetAsyncKeyState($vk) -band 0x8000) -ne 0)
}

function GetCursorPoint() {
  $pt = New-Object NativeInput+POINT
  [void][NativeInput]::GetCursorPos([ref]$pt)
  return @{ x = [int]$pt.X; y = [int]$pt.Y }
}

function ShowClickCard([int]$x, [int]$y, [int]$durationMs) {
  if ($durationMs -le 0) { return }

  $screen = [System.Windows.Forms.SystemInformation]::VirtualScreen
  $screenLeft = [int]$screen.Left
  $screenTop = [int]$screen.Top
  $screenRight = [int]$screen.Right
  $screenBottom = [int]$screen.Bottom

  $cardWidth = 190
  $cardHeight = 56
  $gap = 8

  $maxLeft = [Math]::Max($screenLeft, $screenRight - $cardWidth)
  $maxTop = [Math]::Max($screenTop, $screenBottom - $cardHeight)

  $left = [int]($x + $gap)
  if ($left + $cardWidth -gt $screenRight) {
    $left = [int]($x - $cardWidth - $gap)
  }
  $left = ClampInt $left $screenLeft $maxLeft

  $top = ClampInt ([int]($y + $gap)) $screenTop $maxTop

  $card = New-Object System.Windows.Forms.Form
  $card.AutoScaleMode = [System.Windows.Forms.AutoScaleMode]::None
  $card.ClientSize = New-Object System.Drawing.Size -ArgumentList $cardWidth, $cardHeight
  $card.StartPosition = [System.Windows.Forms.FormStartPosition]::Manual
  $card.Location = New-Object System.Drawing.Point -ArgumentList $left, $top
  $card.TopMost = $true
  $card.ShowInTaskbar = $false
  $card.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::FixedSingle
  $card.ControlBox = $false
  $card.BackColor = [System.Drawing.Color]::FromArgb(255, 31, 35, 42)
  $card.Opacity = 0.97

  $title = New-Object System.Windows.Forms.Label
  $title.AutoSize = $false
  $title.Location = New-Object System.Drawing.Point -ArgumentList 8, 6
  $title.Size = New-Object System.Drawing.Size -ArgumentList ($cardWidth - 16), 18
  $title.ForeColor = [System.Drawing.Color]::White
  $title.Text = "Captured point"

  $coords = New-Object System.Windows.Forms.Label
  $coords.AutoSize = $false
  $coords.Location = New-Object System.Drawing.Point -ArgumentList 8, 26
  $coords.Size = New-Object System.Drawing.Size -ArgumentList ($cardWidth - 16), 20
  $coords.ForeColor = [System.Drawing.Color]::White
  $coords.Text = ("X: {0}    Y: {1}" -f [int]$x, [int]$y)

  [void]$card.Controls.Add($title)
  [void]$card.Controls.Add($coords)

  try {
    $card.Show()
    $card.Refresh()
    [System.Windows.Forms.Application]::DoEvents()
    Start-Sleep -Milliseconds $durationMs
  } finally {
    try { $card.Close() } catch {}
    $card.Dispose()
  }
}

$outputAbs = [System.IO.Path]::GetFullPath($OutputPath)
EnsureDir ([System.IO.Path]::GetDirectoryName($outputAbs))

$points = New-Object System.Collections.ArrayList
$cardMsSafe = [Math]::Max(0, [int]$CardMs)
$pollMsSafe = [Math]::Max(5, [int]$PollMs)
$leftVk = 0x01
$escVk = 0x1B
$leftWasDown = $false

$status = New-Object System.Windows.Forms.Form
$status.AutoScaleMode = [System.Windows.Forms.AutoScaleMode]::None
$status.ClientSize = New-Object System.Drawing.Size -ArgumentList 330, 118
$status.StartPosition = [System.Windows.Forms.FormStartPosition]::Manual
$status.Location = New-Object System.Drawing.Point -ArgumentList 16, 16
$status.TopMost = $true
$status.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::FixedToolWindow
$status.Text = "Point Picker"

$line1 = New-Object System.Windows.Forms.Label
$line1.AutoSize = $false
$line1.Location = New-Object System.Drawing.Point -ArgumentList 10, 10
$line1.Size = New-Object System.Drawing.Size -ArgumentList 310, 20
$line1.Text = "Left click: capture point"

$line2 = New-Object System.Windows.Forms.Label
$line2.AutoSize = $false
$line2.Location = New-Object System.Drawing.Point -ArgumentList 10, 32
$line2.Size = New-Object System.Drawing.Size -ArgumentList 310, 20
$line2.Text = "Esc: finish and save JSON"

$line3 = New-Object System.Windows.Forms.Label
$line3.AutoSize = $false
$line3.Location = New-Object System.Drawing.Point -ArgumentList 10, 58
$line3.Size = New-Object System.Drawing.Size -ArgumentList 310, 20
$line3.Text = "Count: 0"

$line4 = New-Object System.Windows.Forms.Label
$line4.AutoSize = $false
$line4.Location = New-Object System.Drawing.Point -ArgumentList 10, 80
$line4.Size = New-Object System.Drawing.Size -ArgumentList 310, 20
$line4.Text = "Last: X=- Y=-"

[void]$status.Controls.Add($line1)
[void]$status.Controls.Add($line2)
[void]$status.Controls.Add($line3)
[void]$status.Controls.Add($line4)
$status.Show()

Write-Host "Point picker started."
Write-Host "Output: $outputAbs"
Write-Host "Left click to capture, ESC to finish."

try {
  while ($true) {
    [System.Windows.Forms.Application]::DoEvents()

    if (IsKeyDown $escVk) {
      break
    }

    $leftDown = IsKeyDown $leftVk
    if (-not $leftDown -and $leftWasDown) {
      $p = GetCursorPoint
      $index = $points.Count + 1
      $entry = [ordered]@{
        index = [int]$index
        x = [int]$p.x
        y = [int]$p.y
        ts = [DateTime]::UtcNow.ToString("o")
      }
      [void]$points.Add([pscustomobject]$entry)

      $line3.Text = "Count: $index"
      $line4.Text = ("Last: X={0} Y={1}" -f [int]$p.x, [int]$p.y)
      Write-Host ("[{0}] x={1} y={2}" -f $index, [int]$p.x, [int]$p.y)

      ShowClickCard ([int]$p.x) ([int]$p.y) $cardMsSafe
    }

    $leftWasDown = $leftDown
    Start-Sleep -Milliseconds $pollMsSafe
  }
} finally {
  try { $status.Close() } catch {}
  $status.Dispose()
}

$json = @($points) | ConvertTo-Json -Depth 10
Write-Utf8NoBom -path $outputAbs -content $json

@{
  outputPath = $outputAbs
  pointsTotal = $points.Count
} | ConvertTo-Json -Compress
