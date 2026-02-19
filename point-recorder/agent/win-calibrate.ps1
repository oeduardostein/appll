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

function Write-Utf8NoBom([string]$path, [string]$content) {
  $encoding = New-Object System.Text.UTF8Encoding($false)
  [System.IO.File]::WriteAllText($path, $content, $encoding)
}

function Apply-CircleRegion([System.Windows.Forms.Form]$form) {
  $width = [Math]::Max(1, [int]$form.Width)
  $height = [Math]::Max(1, [int]$form.Height)
  $path = New-Object System.Drawing.Drawing2D.GraphicsPath
  $path.AddEllipse(0, 0, $width - 1, $height - 1)
  $form.Region = New-Object System.Drawing.Region($path)
  $path.Dispose()
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

function HasPoint($event) {
  if ($null -eq $event) { return $false }
  if (-not ($event.PSObject.Properties.Name -contains "x")) { return $false }
  if (-not ($event.PSObject.Properties.Name -contains "y")) { return $false }
  if (-not (IsNumericLike $event.x)) { return $false }
  if (-not (IsNumericLike $event.y)) { return $false }
  return $true
}

function GetTypeLower($event) {
  if ($null -eq $event) { return "" }
  if (-not ($event.PSObject.Properties.Name -contains "type")) { return "" }
  return ([string]$event.type).ToLowerInvariant()
}

function GetCalibrationTargets($events) {
  $targets = New-Object System.Collections.Generic.List[object]
  $i = 0

  while ($i -lt $events.Count) {
    $ev = $events[$i]
    if (-not (HasPoint $ev)) {
      $i += 1
      continue
    }

    $typeLower = GetTypeLower $ev
    $x = ToIntCoord $ev.x
    $y = ToIntCoord $ev.y
    $indexes = New-Object System.Collections.Generic.List[int]
    $indexes.Add($i)
    $slotIndex = -1
    $titleType = if ([string]::IsNullOrWhiteSpace($typeLower)) { "point" } else { $typeLower }

    if ($typeLower -eq "slot_begin") {
      $slotIndex = $i
      $slotName = ""
      if ($ev.PSObject.Properties.Name -contains "name") {
        $slotName = [string]$ev.name
      }
      if (-not [string]::IsNullOrWhiteSpace($slotName)) {
        $titleType = "slot:$slotName"
      }

      $mouseDownIdx = $i + 1
      if ($mouseDownIdx -lt $events.Count) {
        $nextEv = $events[$mouseDownIdx]
        if ((GetTypeLower $nextEv) -eq "mouse_down" -and (HasPoint $nextEv)) {
          $indexes.Add($mouseDownIdx)
          $mouseUpIdx = $mouseDownIdx + 1
          if ($mouseUpIdx -lt $events.Count) {
            $nextUp = $events[$mouseUpIdx]
            if ((GetTypeLower $nextUp) -eq "mouse_up" -and (HasPoint $nextUp)) {
              $indexes.Add($mouseUpIdx)
              $i = $mouseUpIdx + 1
            } else {
              $i = $mouseDownIdx + 1
            }
          } else {
            $i = $mouseDownIdx + 1
          }
        } else {
          $i += 1
        }
      } else {
        $i += 1
      }
    } elseif ($typeLower -eq "mouse_down") {
      $mouseUpIdx = $i + 1
      if ($mouseUpIdx -lt $events.Count) {
        $nextUp = $events[$mouseUpIdx]
        if ((GetTypeLower $nextUp) -eq "mouse_up" -and (HasPoint $nextUp)) {
          $indexes.Add($mouseUpIdx)
          $i = $mouseUpIdx + 1
        } else {
          $i += 1
        }
      } else {
        $i += 1
      }
      $titleType = "click"
    } else {
      $i += 1
    }

    $targets.Add([pscustomobject]@{
      primaryIndex = [int]$indexes[0]
      slotIndex = [int]$slotIndex
      indexes = @($indexes)
      x = $x
      y = $y
      titleType = $titleType
    })
  }

  return $targets
}

function Show-CalibrationMarker(
  [int]$initialX,
  [int]$initialY,
  [string]$titleText,
  [string]$helpText
) {
  $markerSize = 12
  $markerOpacity = (235.0 / 255.0)
  $markerColor = [System.Drawing.Color]::FromArgb(255, 230, 36, 36)
  $screen = [System.Windows.Forms.SystemInformation]::VirtualScreen
  $screenLeft = [int]$screen.Left
  $screenTop = [int]$screen.Top
  $screenRight = [int]$screen.Right
  $screenBottom = [int]$screen.Bottom
  $maxLeftBound = [Math]::Max($screenLeft, $screenRight - $markerSize)
  $maxTopBound = [Math]::Max($screenTop, $screenBottom - $markerSize)
  $half = [int]($markerSize / 2)

  $startLeft = Clamp ($initialX - $half) $screenLeft $maxLeftBound
  $startTop = Clamp ($initialY - $half) $screenTop $maxTopBound

  $form = New-Object System.Windows.Forms.Form
  $form.Text = $titleText
  $form.Width = $markerSize
  $form.Height = $markerSize
  $form.StartPosition = [System.Windows.Forms.FormStartPosition]::Manual
  $form.Location = New-Object System.Drawing.Point($startLeft, $startTop)
  $form.TopMost = $true
  $form.ShowInTaskbar = $false
  $form.FormBorderStyle = [System.Windows.Forms.FormBorderStyle]::None
  $form.BackColor = $markerColor
  $form.Opacity = $markerOpacity
  $form.KeyPreview = $true
  Apply-CircleRegion $form

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
    slotName = $null
  }

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
    $form.Left = Clamp $newLeft $screenLeft $maxLeftBound
    $form.Top = Clamp $newTop $screenTop $maxTopBound
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
      ([System.Windows.Forms.Keys]::F6) {
        $result.action = "mark_slot"
        $result.slotName = "cpf_cgc"
        $result.x = [int]($form.Left + ($form.Width / 2))
        $result.y = [int]($form.Top + ($form.Height / 2))
        $form.Close()
      }
      ([System.Windows.Forms.Keys]::F7) {
        $result.action = "mark_slot"
        $result.slotName = "nome"
        $result.x = [int]($form.Left + ($form.Width / 2))
        $result.y = [int]($form.Top + ($form.Height / 2))
        $form.Close()
      }
      ([System.Windows.Forms.Keys]::F8) {
        $result.action = "mark_slot"
        $result.slotName = "chassi"
        $result.x = [int]($form.Left + ($form.Width / 2))
        $result.y = [int]($form.Top + ($form.Height / 2))
        $form.Close()
      }
      ([System.Windows.Forms.Keys]::F9) {
        $result.action = "mark_slot"
        $result.slotName = "senha"
        $result.x = [int]($form.Left + ($form.Width / 2))
        $result.y = [int]($form.Top + ($form.Height / 2))
        $form.Close()
      }
      ([System.Windows.Forms.Keys]::Left) {
        $form.Left = Clamp ($form.Left - 1) $screenLeft $maxLeftBound
      }
      ([System.Windows.Forms.Keys]::Right) {
        $form.Left = Clamp ($form.Left + 1) $screenLeft $maxLeftBound
      }
      ([System.Windows.Forms.Keys]::Up) {
        $form.Top = Clamp ($form.Top - 1) $screenTop $maxTopBound
      }
      ([System.Windows.Forms.Keys]::Down) {
        $form.Top = Clamp ($form.Top + 1) $screenTop $maxTopBound
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
$targets = GetCalibrationTargets $eventsList
$rawPointCount = 0
for ($scan = 0; $scan -lt $eventsList.Count; $scan++) {
  if (HasPoint $eventsList[$scan]) { $rawPointCount += 1 }
}

if ($targets.Count -eq 0) {
  EnsureDir (Split-Path -Parent $OutputPath)
  Write-Utf8NoBom -path $OutputPath -content ($eventsList | ConvertTo-Json -Depth 100)
  @{
    outputPath = (Resolve-Path -LiteralPath $OutputPath).Path
    pointsTotal = 0
    pointsRaw = $rawPointCount
    pointsChanged = 0
    pointsSkipped = 0
    canceled = $false
    message = "Nenhum ponto com coordenadas encontrado no template."
  } | ConvertTo-Json -Compress
  exit 0
}

Write-Host "Calibracao iniciada. Atalhos: Enter=confirmar, S=manter ponto original, Esc=cancelar, F6/F7/F8/F9=marcar slot."
Write-Host "Modo rapido: calibrando por acao de clique/slot/screenshot."
Write-Host "Dica: arraste a bolinha vermelha para o local correto."

$changed = 0
$skipped = 0
$slotMarks = 0
$canceled = $false
$pendingSlotInserts = New-Object System.Collections.Generic.List[object]

for ($pos = 0; $pos -lt $targets.Count; $pos++) {
  $target = $targets[$pos]
  $index = [int]$target.primaryIndex
  $x = ToIntCoord $target.x
  $y = ToIntCoord $target.y
  $type = [string]$target.titleType
  $stepTitle = "Acao $($pos + 1)/$($targets.Count) - idx $index - $type"
  $help = "Arraste este marcador para o ponto correto. Enter confirma. S mantém. Esc cancela. F6=cpf_cgc, F7=nome, F8=chassi, F9=senha."

  Write-Host "[$($pos + 1)/$($targets.Count)] Ajustando acao idx=$index type=$type x=$x y=$y"

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

  foreach ($targetIndex in @($target.indexes)) {
    $ti = [int]$targetIndex
    if ($ti -lt 0 -or $ti -ge $eventsList.Count) { continue }
    $eventsList[$ti].x = $newX
    $eventsList[$ti].y = $newY
  }

  if ($result.action -eq "mark_slot") {
    $slotName = [string]$result.slotName

    $slotIdx = [int]$target.slotIndex
    if ($slotIdx -ge 0 -and $slotIdx -lt $eventsList.Count) {
      $eventsList[$slotIdx].type = "slot_begin"
      $eventsList[$slotIdx].name = $slotName
      $eventsList[$slotIdx].x = $newX
      $eventsList[$slotIdx].y = $newY
    } else {
      $pendingSlotInserts.Add([pscustomobject]@{
        index = $index
        name = $slotName
        x = $newX
        y = $newY
      })
    }

    $slotMarks += 1
    Write-Host "  -> slot marcado: $slotName (idx=$index x=$newX y=$newY)"
    continue
  }
}

if ($canceled) {
  throw "Calibracao cancelada pelo usuario."
}

if ($pendingSlotInserts.Count -gt 0) {
  $eventsMutable = New-Object System.Collections.ArrayList
  foreach ($item in $eventsList) { [void]$eventsMutable.Add($item) }

  $orderedInserts = @($pendingSlotInserts | Sort-Object -Property index -Descending)
  foreach ($insert in $orderedInserts) {
    $insertIndex = [int]$insert.index
    if ($insertIndex -lt 0) { $insertIndex = 0 }
    if ($insertIndex -gt $eventsMutable.Count) { $insertIndex = $eventsMutable.Count }

    $slotEvent = [ordered]@{
      type = "slot_begin"
      name = [string]$insert.name
      x = [int]$insert.x
      y = [int]$insert.y
    }

    if ($insertIndex -lt $eventsMutable.Count) {
      $anchor = $eventsMutable[$insertIndex]
      if ($anchor.PSObject.Properties.Name -contains "t" -and $null -ne $anchor.t) {
        $slotEvent.t = $anchor.t
      }
      if ($anchor.PSObject.Properties.Name -contains "ts" -and $null -ne $anchor.ts) {
        $slotEvent.ts = $anchor.ts
      }
    }

    [void]$eventsMutable.Insert($insertIndex, [pscustomobject]$slotEvent)
  }

  $eventsList = @($eventsMutable)
}

EnsureDir (Split-Path -Parent $OutputPath)
Write-Utf8NoBom -path $OutputPath -content ($eventsList | ConvertTo-Json -Depth 100)

@{
  outputPath = (Resolve-Path -LiteralPath $OutputPath).Path
  pointsTotal = $targets.Count
  pointsRaw = $rawPointCount
  pointsChanged = $changed
  pointsSkipped = $skipped
  slotsMarked = $slotMarks
  canceled = $false
} | ConvertTo-Json -Compress
