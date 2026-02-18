param(
  [Parameter(Mandatory = $true)][string]$TemplatePath,
  [Parameter(Mandatory = $true)][string]$OutputPath
)

$ErrorActionPreference = "Stop"

Add-Type -AssemblyName System.Windows.Forms
Add-Type -AssemblyName System.Drawing

function EnsureDir([string]$dir) {
  if (-not (Test-Path -LiteralPath $dir)) {
    New-Item -ItemType Directory -Path $dir -Force | Out-Null
  }
}

function Clamp([int]$value, [int]$min, [int]$max) {
  if ($value -lt $min) { return $min }
  if ($value -gt $max) { return $max }
  return $value
}

function IsNumericLike($value) {
  if ($null -eq $value) { return $false }
  try {
    [void][double]$value
    return $true
  } catch {
    return $false
  }
}

function ToIntCoord($value) {
  if ($null -eq $value) { return 0 }
  return [int][Math]::Round([double]$value)
}

function GetPointIndexes($events) {
  $indexes = New-Object System.Collections.Generic.List[int]
  for ($i = 0; $i -lt $events.Count; $i++) {
    $ev = $events[$i]
    if ($null -eq $ev) { continue }
    if (-not ($ev.PSObject.Properties.Name -contains "x")) { continue }
    if (-not ($ev.PSObject.Properties.Name -contains "y")) { continue }
    if (-not (IsNumericLike $ev.x)) { continue }
    if (-not (IsNumericLike $ev.y)) { continue }
    $indexes.Add($i)
  }
  return $indexes
}

function Show-CalibrationMarker(
  [int]$initialX,
  [int]$initialY,
  [string]$titleText,
  [string]$helpText
) {
  $markerSize = 44
  $screen = [System.Windows.Forms.SystemInformation]::VirtualScreen
  $half = [int]($markerSize / 2)

  $startLeft = Clamp ($initialX - $half) $screen.Left ($screen.Right - $markerSize)
  $startTop = Clamp ($initialY - $half) $screen.Top ($screen.Bottom - $markerSize)

  $form = New-Object System.Windows.Forms.Form
  $form.Text = $titleText
  $form.Width = $markerSize
  $form.Height = $markerSize
  $form.StartPosition = [System.Windows.Forms.FormStartPosition]::Manual
  $form.Location = New-Object System.Drawing.Point($startLeft, $startTop)
  $form.TopMost = $true
  $form.ShowInTaskbar = $false
  $form.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::None
  $form.BackColor = [System.Drawing.Color]::Black
  $form.Opacity = 0.95
  $form.KeyPreview = $true

  $toolTip = New-Object System.Windows.Forms.ToolTip
  $toolTip.IsBalloon = $true
  $toolTip.ToolTipTitle = "Calibracao de ponto"
  $toolTip.Show($helpText, $form, 2500)

  $dragState = [pscustomobject]@{
    IsDragging = $false
    StartX = 0
    StartY = 0
  }
  $result = [ordered]@{
    action = "cancel"
    x = $initialX
    y = $initialY
  }

  $form.Add_Paint({
    param($sender, $e)
    $g = $e.Graphics
    $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::AntiAlias

    $rect = New-Object System.Drawing.Rectangle(2, 2, $sender.ClientSize.Width - 5, $sender.ClientSize.Height - 5)
    $fillBrush = New-Object System.Drawing.SolidBrush([System.Drawing.Color]::FromArgb(210, 239, 68, 68))
    $strokePen = New-Object System.Drawing.Pen([System.Drawing.Color]::White, 2)
    $crossPen = New-Object System.Drawing.Pen([System.Drawing.Color]::White, 1)

    $g.FillEllipse($fillBrush, $rect)
    $g.DrawEllipse($strokePen, $rect)

    $centerX = [int]($sender.ClientSize.Width / 2)
    $centerY = [int]($sender.ClientSize.Height / 2)
    $g.DrawLine($crossPen, $centerX, 6, $centerX, $sender.ClientSize.Height - 7)
    $g.DrawLine($crossPen, 6, $centerY, $sender.ClientSize.Width - 7, $centerY)

    $crossPen.Dispose()
    $strokePen.Dispose()
    $fillBrush.Dispose()
  })

  $onMouseDown = {
    param($sender, $e)
    if ($e.Button -ne [System.Windows.Forms.MouseButtons]::Left) { return }
    $dragState.IsDragging = $true
    $dragState.StartX = $e.X
    $dragState.StartY = $e.Y
  }
  $onMouseMove = {
    param($sender, $e)
    if (-not $dragState.IsDragging) { return }
    $currentScreenPos = $form.PointToScreen((New-Object System.Drawing.Point($e.X, $e.Y)))
    $newLeft = $currentScreenPos.X - $dragState.StartX
    $newTop = $currentScreenPos.Y - $dragState.StartY
    $form.Left = Clamp $newLeft $screen.Left ($screen.Right - $form.Width)
    $form.Top = Clamp $newTop $screen.Top ($screen.Bottom - $form.Height)
  }
  $onMouseUp = {
    $dragState.IsDragging = $false
  }

  $form.Add_MouseDown($onMouseDown)
  $form.Add_MouseMove($onMouseMove)
  $form.Add_MouseUp($onMouseUp)

  $form.Add_KeyDown({
    param($sender, $e)
    switch ($e.KeyCode) {
      ([System.Windows.Forms.Keys]::Enter) {
        $result.action = "confirm"
        $result.x = [int]($form.Left + ($form.Width / 2))
        $result.y = [int]($form.Top + ($form.Height / 2))
        $form.Close()
      }
      ([System.Windows.Forms.Keys]::S) {
        $result.action = "skip"
        $form.Close()
      }
      ([System.Windows.Forms.Keys]::Escape) {
        $result.action = "cancel"
        $form.Close()
      }
      ([System.Windows.Forms.Keys]::Left) {
        $form.Left = Clamp ($form.Left - 1) $screen.Left ($screen.Right - $form.Width)
      }
      ([System.Windows.Forms.Keys]::Right) {
        $form.Left = Clamp ($form.Left + 1) $screen.Left ($screen.Right - $form.Width)
      }
      ([System.Windows.Forms.Keys]::Up) {
        $form.Top = Clamp ($form.Top - 1) $screen.Top ($screen.Bottom - $form.Height)
      }
      ([System.Windows.Forms.Keys]::Down) {
        $form.Top = Clamp ($form.Top + 1) $screen.Top ($screen.Bottom - $form.Height)
      }
    }
  })

  $form.Add_Shown({
    $form.Activate() | Out-Null
    $form.Focus() | Out-Null
  })

  [void]$form.ShowDialog()
  $form.Dispose()

  return [pscustomobject]$result
}

$events = Get-Content -LiteralPath $TemplatePath -Raw | ConvertFrom-Json
if ($events -isnot [System.Collections.IEnumerable]) {
  throw "Template invalido: esperado array JSON."
}

# Force indexable list
$eventsList = @($events)
$pointIndexes = GetPointIndexes $eventsList

if ($pointIndexes.Count -eq 0) {
  EnsureDir (Split-Path -Parent $OutputPath)
  $eventsList | ConvertTo-Json -Depth 100 | Set-Content -LiteralPath $OutputPath -Encoding UTF8
  @{
    outputPath = (Resolve-Path -LiteralPath $OutputPath).Path
    pointsTotal = 0
    pointsChanged = 0
    pointsSkipped = 0
    canceled = $false
    message = "Nenhum ponto com coordenadas encontrado no template."
  } | ConvertTo-Json -Compress
  exit 0
}

Write-Host "Calibracao iniciada. Atalhos: Enter=confirmar, S=manter ponto original, Esc=cancelar."
Write-Host "Dica: arraste o marcador vermelho para o local correto de cada ponto."

$changed = 0
$skipped = 0
$canceled = $false

for ($pos = 0; $pos -lt $pointIndexes.Count; $pos++) {
  $index = $pointIndexes[$pos]
  $ev = $eventsList[$index]
  $x = ToIntCoord $ev.x
  $y = ToIntCoord $ev.y
  $type = [string]$ev.type
  $stepTitle = "Ponto $($pos + 1)/$($pointIndexes.Count) - idx $index - $type"
  $help = "Arraste este marcador para o ponto correto. Enter confirma. S mantém. Esc cancela."

  Write-Host "[$($pos + 1)/$($pointIndexes.Count)] Ajustando evento idx=$index type=$type x=$x y=$y"

  $result = Show-CalibrationMarker -initialX $x -initialY $y -titleText $stepTitle -helpText $help
  if ($result.action -eq "cancel") {
    $canceled = $true
    break
  }

  if ($result.action -eq "skip") {
    $skipped += 1
    continue
  }

  $newX = ToIntCoord $result.x
  $newY = ToIntCoord $result.y
  if ($newX -ne $x -or $newY -ne $y) {
    $changed += 1
  }

  $eventsList[$index].x = $newX
  $eventsList[$index].y = $newY
}

if ($canceled) {
  throw "Calibracao cancelada pelo usuario."
}

EnsureDir (Split-Path -Parent $OutputPath)
$eventsList | ConvertTo-Json -Depth 100 | Set-Content -LiteralPath $OutputPath -Encoding UTF8

@{
  outputPath = (Resolve-Path -LiteralPath $OutputPath).Path
  pointsTotal = $pointIndexes.Count
  pointsChanged = $changed
  pointsSkipped = $skipped
  canceled = $false
} | ConvertTo-Json -Compress
