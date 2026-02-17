param(
  [Parameter(Mandatory = $true)][string]$TemplatePath,
  [Parameter(Mandatory = $true)][string]$DataPath,
  [Parameter(Mandatory = $true)][string]$ScreenshotsDir,
  [int]$MaxDelayMs = 5000,
  [double]$Speed = 1.0,
  [string]$ReplayText = "false",
  [int]$PreReplayWaitMs = 0,
  [int]$PostLoginWaitMs = 0,
  [int]$CropW = 0,
  [int]$CropH = 0
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
    # uiohook keycodes: usamos mapeamento conservador para não confundir
    # teclas de digitação (2..13 etc.) com TAB/ENTER/BACKSPACE.
    if ($code -eq 28 -or $code -eq 13) { return "{ENTER}" }
    if ($code -eq 15) { return "{TAB}" }
    if ($code -eq 1  -or $code -eq 27) { return "{ESC}" }
    if ($code -eq 14) { return "{BACKSPACE}" }
    if ($code -eq 57) { return " " }

    if ($code -eq 57416 -or $code -eq 38) { return "{UP}" }
    if ($code -eq 57424 -or $code -eq 40) { return "{DOWN}" }
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
  # Clipboard.SetText pode lançar exceção com string vazia.
  # Para campos "vazios" (ex.: parte central do CPF), limpamos o campo e seguimos.
  if ([string]::IsNullOrEmpty($text)) {
    [System.Windows.Forms.SendKeys]::SendWait("^a")
    Start-Sleep -Milliseconds 60
    [System.Windows.Forms.SendKeys]::SendWait("{BACKSPACE}")
    return
  }
  [System.Windows.Forms.Clipboard]::SetText([string]$text)
  [System.Windows.Forms.SendKeys]::SendWait("^a")
  Start-Sleep -Milliseconds 60
  [System.Windows.Forms.SendKeys]::SendWait("^v")
}

function OnlyDigits([string]$value) {
  if ($null -eq $value) { return "" }
  return [regex]::Replace($value, "\D", "")
}

function BuildCpfCnpjSegments([string]$cpfCgc) {
  $digits = OnlyDigits $cpfCgc

  if ($digits.Length -eq 11) {
    return @(
      $digits.Substring(0, 9),
      "",
      $digits.Substring(9, 2)
    )
  }

  if ($digits.Length -eq 14) {
    return @(
      $digits.Substring(0, 8),
      $digits.Substring(8, 4),
      $digits.Substring(12, 2)
    )
  }

  throw "cpf_cgc inválido. Esperado CPF (11 dígitos) ou CNPJ (14 dígitos). Valor recebido: '$cpfCgc'."
}

function TakeScreenshot([string]$dir, [int]$cropW, [int]$cropH, [int]$centerX, [int]$centerY, [bool]$hasCenter) {
  EnsureDir $dir
  $file = Join-Path $dir ("shot_" + [DateTime]::UtcNow.ToString("yyyyMMdd_HHmmss_fff") + ".png")

  $bounds = [System.Windows.Forms.Screen]::PrimaryScreen.Bounds
  $bmp = New-Object System.Drawing.Bitmap $bounds.Width, $bounds.Height
  $graphics = [System.Drawing.Graphics]::FromImage($bmp)
  $graphics.CopyFromScreen($bounds.X, $bounds.Y, 0, 0, $bounds.Size)
  $graphics.Dispose()

  if ($cropW -gt 0 -and $cropH -gt 0 -and $hasCenter) {
    $halfW = [Math]::Floor($cropW / 2)
    $halfH = [Math]::Floor($cropH / 2)
    $x = [Math]::Max(0, $centerX - $halfW)
    $y = [Math]::Max(0, $centerY - $halfH)
    if ($x + $cropW -gt $bounds.Width) { $x = [Math]::Max(0, $bounds.Width - $cropW) }
    if ($y + $cropH -gt $bounds.Height) { $y = [Math]::Max(0, $bounds.Height - $cropH) }

    $cropRect = New-Object System.Drawing.Rectangle $x, $y, $cropW, $cropH
    $cropBmp = $bmp.Clone($cropRect, $bmp.PixelFormat)
    $cropBmp.Save($file, [System.Drawing.Imaging.ImageFormat]::Png)
    $cropBmp.Dispose()
    $bmp.Dispose()
  } else {
    $bmp.Save($file, [System.Drawing.Imaging.ImageFormat]::Png)
    $bmp.Dispose()
  }

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
$slotState = $null
$lastScreenshot = $null
$lastMouseX = 0
$lastMouseY = 0
$hasMouse = $false

if ($PreReplayWaitMs -gt 0) {
  SleepMs $PreReplayWaitMs
}

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
    if ($MaxDelayMs -gt 0) {
      SleepMs ([Math]::Min($MaxDelayMs, $scaled))
    } else {
      SleepMs $scaled
    }
  }
  if ($null -ne $t) { $lastT = $t }

  $type = [string]$ev.type

  if ($type -eq "slot_begin") {
    $name = [string]$ev.name
    if (-not [string]::IsNullOrWhiteSpace($name)) {
      $slotActive = $name
      $slotState = $null

      $hasSlotX = $ev.PSObject.Properties.Name -contains "x"
      $hasSlotY = $ev.PSObject.Properties.Name -contains "y"
      if ($hasSlotX -and $hasSlotY -and $null -ne $ev.x -and $null -ne $ev.y) {
        $sx = [int]$ev.x
        $sy = [int]$ev.y
        $lastMouseX = $sx
        $lastMouseY = $sy
        $hasMouse = $true
        [WinInput]::SetCursorPos($sx, $sy) | Out-Null
        MouseDown "left"
        Start-Sleep -Milliseconds 40
        MouseUp "left"
        Start-Sleep -Milliseconds 90
      }

      if ($name -eq "cpf_cgc") {
        $segments = BuildCpfCnpjSegments ([string]$data.$name)
        # Preenchimento determinístico de CPF/CNPJ:
        # campo 1 -> TAB -> campo 2 -> TAB -> campo 3.
        PasteText ([string]$segments[0])
        Start-Sleep -Milliseconds 60
        [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        Start-Sleep -Milliseconds 60
        PasteText ([string]$segments[1])
        Start-Sleep -Milliseconds 60
        [System.Windows.Forms.SendKeys]::SendWait("{TAB}")
        Start-Sleep -Milliseconds 60
        PasteText ([string]$segments[2])
        $slotState = @{
          mode = "ignore_until_mouse"
        }
      } elseif ($name -eq "senha") {
        $value = $data.$name
        if ($null -eq $value) { $value = "" }
        $text = [string]$value
        PasteText $text
        if (-not [string]::IsNullOrWhiteSpace($text)) {
          Start-Sleep -Milliseconds 120
          [System.Windows.Forms.SendKeys]::SendWait("{ENTER}")
          if ($PostLoginWaitMs -gt 0) {
            SleepMs $PostLoginWaitMs
          }
        }
        # Após senha+ENTER, ignorar qualquer tecla gravada até o próximo clique.
        # Isso evita "vazar" teclas da gravação para o campo de login.
        $slotState = @{
          mode = "ignore_until_mouse"
        }
      } else {
        $value = $data.$name
        if ($null -eq $value) { $value = "" }
        PasteText ([string]$value)
        $slotState = @{
          mode = "ignore_until_mouse"
        }
      }
    }
    continue
  }

  if ($type -eq "screenshot") {
    $lastScreenshot = TakeScreenshot $ScreenshotsDir $CropW $CropH $lastMouseX $lastMouseY $hasMouse
    continue
  }

  if ($type -eq "mouse_down") {
    $slotActive = $null
    $slotState = $null
    $x = [int]$ev.x
    $y = [int]$ev.y
    $lastMouseX = $x
    $lastMouseY = $y
    $hasMouse = $true
    [WinInput]::SetCursorPos($x, $y) | Out-Null
    MouseDown (MapButton([int]$ev.button))
    continue
  }

  if ($type -eq "mouse_up") {
    $x = [int]$ev.x
    $y = [int]$ev.y
    $lastMouseX = $x
    $lastMouseY = $y
    $hasMouse = $true
    [WinInput]::SetCursorPos($x, $y) | Out-Null
    MouseUp (MapButton([int]$ev.button))
    continue
  }

  if ($type -eq "key_down" -or $type -eq "key_up") {
    if ($slotActive -and $slotState -and $slotState.mode -eq "ignore_until_mouse") {
      # Após preencher slot, ignoramos a digitação gravada até o próximo clique
      # para evitar "vazar" teclas de exemplo para o replay.
      continue
    }

    $mapped = MapSpecialKey $ev.keycode $ev.rawcode
    if ($null -eq $mapped) { continue }

    if ($slotActive) {
      if ($mapped -ne "{TAB}" -and $mapped -ne "{ENTER}" -and $mapped -ne "{ESC}") {
        continue
      }

      if ($type -eq "key_up") {
        continue
      }
    }

    if ($type -eq "key_down") {
      [System.Windows.Forms.SendKeys]::SendWait($mapped)
    } else {
      # key_up não é necessário com SendKeys
    }

    if ($slotActive -and $slotActive -ne "cpf_cgc" -and ($mapped -eq "{TAB}" -or $mapped -eq "{ENTER}" -or $mapped -eq "{ESC}")) {
      $slotActive = $null
      $slotState = $null
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
