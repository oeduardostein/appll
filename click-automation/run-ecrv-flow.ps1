param(
  [string]$FlowFile = "e-crv-flow.json",
  [string]$Cpf = "44922011811",
  [string]$Pin = "1234",
  [int]$PreWaitMs = 3000,
  [string]$Url = "https://www.e-crvsp.sp.gov.br/"
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

function SleepMs([int]$ms) {
  if ($ms -le 0) { return }
  Start-Sleep -Milliseconds $ms
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

function MouseClick([int]$x, [int]$y) {
  [WinInput]::SetCursorPos($x, $y) | Out-Null
  Start-Sleep -Milliseconds 100
  MouseDown
  Start-Sleep -Milliseconds 100
  MouseUp
  Start-Sleep -Milliseconds 100
}

function MouseDoubleClick([int]$x, [int]$y) {
  MouseClick $x $y
  Start-Sleep -Milliseconds 120
  MouseClick $x $y
}

function PasteText([string]$text) {
  [System.Windows.Forms.Clipboard]::SetText([string]$text)
  Start-Sleep -Milliseconds 50
  [System.Windows.Forms.SendKeys]::SendWait("^a")
  Start-Sleep -Milliseconds 30
  [System.Windows.Forms.SendKeys]::SendWait("^v")
}

function TypeText([string]$text) {
  foreach ($ch in $text.ToCharArray()) {
    [System.Windows.Forms.SendKeys]::SendWait($ch)
    Start-Sleep -Milliseconds 80
  }
}

function ShowDebugMarker([int]$x, [int]$y, [string]$text, [int]$durationMs) {
  if ($durationMs -le 0) { return }

  $dotWidth = 20
  $dotHeight = 20
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
  $markerForm.ClientSize = New-Object System.Drawing.Size -ArgumentList 200, 40
  $markerForm.StartPosition = [System.Windows.Forms.FormStartPosition]::Manual
  $markerForm.Location = New-Object System.Drawing.Point -ArgumentList ($markerLeft + 15), ($markerTop + 15)
  $markerForm.TopMost = $true
  $markerForm.ShowInTaskbar = $false
  $markerForm.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::None
  $markerForm.BackColor = [System.Drawing.Color]::FromArgb(255, 31, 35, 42)
  $markerForm.Opacity = 0.95

  $label = New-Object System.Windows.Forms.Label
  $label.Text = $text
  $label.ForeColor = [System.Drawing.Color]::White
  $label.Font = New-Object System.Drawing.Font("Segoe UI", 10, [System.Drawing.FontStyle]::Bold)
  $label.AutoSize = $false
  $label.Location = New-Object System.Drawing.Point -ArgumentList 10, 10
  $label.Size = New-Object System.Drawing.Size -ArgumentList 180, 20

  [void]$markerForm.Controls.Add($label)

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

function GetClipboardTextWithRetry([int]$attempts = 8, [int]$delayMs = 300) {
  for ($i = 1; $i -le $attempts; $i++) {
    try {
      $text = [System.Windows.Forms.Clipboard]::GetText()
      if (-not [string]::IsNullOrWhiteSpace($text)) {
        return $text
      }
    } catch {
      # Clipboard pode estar ocupado por outro processo
    }

    Start-Sleep -Milliseconds $delayMs
  }

  return ""
}

# Abrir Chrome com URL antes de iniciar (apenas se URL foi fornecida)
$chromeOpened = $false
if ($Url) {
  Write-Host "========================================"
  Write-Host "Abrindo Chrome..."
  Write-Host "URL: $Url"
  Write-Host "========================================"

  $chromePaths = @(
    "C:\Program Files\Google\Chrome\Application\chrome.exe",
    "C:\Program Files (x86)\Google\Chrome\Application\chrome.exe",
    "$env:LOCALAPPDATA\Google\Chrome\Application\chrome.exe",
    "$env:PROGRAMFILES\Google\Chrome\Application\chrome.exe",
    "$env:PROGRAMFILES(X86)\Google\Chrome\Application\chrome.exe"
  )

  $chromePath = $null
  foreach ($path in $chromePaths) {
    if (Test-Path $path) {
      $chromePath = $path
      break
    }
  }

  if ($chromePath) {
    Write-Host "Chrome encontrado em: $chromePath"

    # Verificar se ja existe Chrome rodando com porta 9222
    $existingPort = netstat -ano | Select-String "9222" | Select-String "LISTENING"
    if ($existingPort) {
      Write-Host "Chrome ja esta rodando com porta 9222"
      $chromeOpened = $true
    } else {
      Write-Host "Iniciando Chrome com --remote-debugging-port=9222"
      Start-Process -FilePath $chromePath -ArgumentList "--remote-debugging-port=9222", "--start-maximized", $Url
      $chromeOpened = $true
    }
  } else {
    # Tentar abrir usando o comando 'start' do Windows
    Write-Host "Tentando abrir Chrome usando comando start..."
    try {
      Start-Process "chrome.exe" -ArgumentList "--remote-debugging-port=9222", "--start-maximized", $Url -ErrorAction SilentlyContinue
      $chromeOpened = $true
    } catch {
      # Tentar abrir URL direta
      Write-Host "Abrindo URL com navegador padrao..."
      Start-Process $Url
      $chromeOpened = $true
    }
  }

  if ($chromeOpened) {
    Write-Host "Aguardando Chrome abrir (7 segundos)..."
    Start-Sleep -Milliseconds 7000
  }
} else {
  Write-Host "Modo: Chrome ja aberto (sem URL fornecida)"
}

# Carregar flow
$flowFile = [System.IO.Path]::GetFullPath($FlowFile)
if (-not (Test-Path $flowFile)) {
  Write-Host "ERRO: Arquivo de flow nao encontrado: $flowFile"
  exit 1
}

$flow = Get-Content -LiteralPath $flowFile -Raw | ConvertFrom-Json

Write-Host "========================================"
Write-Host "E-CRV SP - Automação de Login"
Write-Host "========================================"
Write-Host "CPF: $Cpf"
Write-Host "PIN: ***"
Write-Host "Passos: $($flow.Length)"
Write-Host "========================================"
Write-Host ""
Write-Host "Iniciando em $PreWaitMs ms..."
Start-Sleep -Milliseconds $PreWaitMs

foreach ($step in $flow) {
  $desc = $step.description
  $action = $step.action

  Write-Host "[$($step.id)] $desc"

  if ($action -eq "click") {
    $x = [int]$step.x
    $y = [int]$step.y
    Write-Host "     Clicando em ($x, $y)..."
    MouseClick $x $y
    ShowDebugMarker $x $y "Clique" 800
    Start-Sleep -Milliseconds 200
  }
  elseif ($action -eq "paste") {
    $val = $step.value
    if ($val -eq "{{CPF}}") { $val = $Cpf }
    if ($val -eq "{{PIN}}") { $val = $Pin }
    PasteText $val
    Start-Sleep -Milliseconds 200
  }
  elseif ($action -eq "type") {
    $val = $step.value
    if ($val -eq "{{CPF}}") { $val = $Cpf }
    if ($val -eq "{{PIN}}") { $val = $Pin }
    TypeText $val
    Start-Sleep -Milliseconds 200
  }
  elseif ($action -eq "wait") {
    $ms = [int]$step.ms
    Write-Host "     Esperando $($ms / 1000) segundos..."
    Start-Sleep -Milliseconds $ms
  }

  Start-Sleep -Milliseconds 300
}

Write-Host ""
Write-Host "========================================"
Write-Host "Automação concluída!"
Write-Host "========================================"

Write-Host ""
Write-Host "========================================"
Write-Host "CAPTURANDO JSESSIONID VIA F12..."
Write-Host "========================================"

Write-Host "Abrindo DevTools (F12)..."
[System.Windows.Forms.SendKeys]::SendWait("{F12}")
Start-Sleep -Seconds 1

Write-Host "Clique 1: (1222, 102)"
MouseClick 1222 102
Start-Sleep -Seconds 1

Write-Host "Clique 2: (1090, 200)"
MouseClick 1090 200
Start-Sleep -Seconds 1

Write-Host "Duplo clique: (1026, 564)"
MouseDoubleClick 1026 564
Start-Sleep -Milliseconds 400

Write-Host "Copiando valor selecionado (Ctrl+C)..."
[System.Windows.Forms.SendKeys]::SendWait("^c")
Start-Sleep -Milliseconds 500

$jsessionid = (GetClipboardTextWithRetry).Trim()

if ([string]::IsNullOrWhiteSpace($jsessionid)) {
  Write-Host "========================================"
  Write-Host "ERRO: Nao foi possivel copiar o valor do JSESSIONID."
  Write-Host "========================================"
  exit 1
}

Write-Host "JSESSIONID copiado: $jsessionid"

$saveScript = Join-Path $PSScriptRoot "save-jsessionid.js"
if (-not (Test-Path $saveScript)) {
  Write-Host "========================================"
  Write-Host "ERRO: Script save-jsessionid.js nao encontrado."
  Write-Host "Caminho: $saveScript"
  Write-Host "========================================"
  exit 1
}

Write-Host "Salvando no banco de dados..."
& node $saveScript $jsessionid
$saveExitCode = $LASTEXITCODE

if ($saveExitCode -ne 0) {
  Write-Host "========================================"
  Write-Host "ERRO: Falha ao salvar JSESSIONID no banco (codigo: $saveExitCode)"
  Write-Host "========================================"
  exit $saveExitCode
}

Write-Host "========================================"
Write-Host "SUCESSO: JSESSIONID copiado e salvo no banco."
Write-Host "========================================"
