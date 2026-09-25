<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>{{ $title ?? 'KhShop Report' }}</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: DejaVu Sans, Helvetica, Arial, sans-serif;
      font-size: 11px;
      color: #171717;
      margin: 0;
      padding: 28px 32px 48px;
    }
    .header {
      border-bottom: 3px solid #25A9EB;
      padding-bottom: 12px;
      margin-bottom: 16px;
    }
    .brand {
      font-size: 20px;
      font-weight: bold;
      color: #25A9EB;
      margin: 0;
    }
    .title {
      font-size: 16px;
      font-weight: bold;
      margin: 6px 0 4px;
      color: #171717;
    }
    .meta {
      color: #525252;
      font-size: 10px;
      margin: 2px 0;
    }
    h2 {
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      color: #525252;
      margin: 18px 0 8px;
      border-bottom: 1px solid #e5e5e5;
      padding-bottom: 4px;
    }
    .kpis {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 8px;
    }
    .kpis td {
      border: 1px solid #e5e5e5;
      padding: 10px;
      width: 25%;
      vertical-align: top;
      background: #fafafa;
    }
    .kpi-label {
      font-size: 9px;
      color: #737373;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      margin: 0 0 4px;
    }
    .kpi-value {
      font-size: 15px;
      font-weight: bold;
      margin: 0;
      color: #171717;
    }
    table.data {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    table.data th {
      background: #f5f5f5;
      border: 1px solid #e5e5e5;
      padding: 7px 8px;
      text-align: left;
      font-size: 9px;
      text-transform: uppercase;
      letter-spacing: 0.04em;
      color: #525252;
    }
    table.data td {
      border: 1px solid #eeeeee;
      padding: 6px 8px;
      vertical-align: top;
    }
    table.data tr:nth-child(even) td { background: #fafafa; }
    .text-right { text-align: right; }
    .bold { font-weight: bold; }
    .muted { color: #a3a3a3; }
    .statement {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }
    .statement td {
      border-bottom: 1px solid #eeeeee;
      padding: 6px 8px;
    }
    .statement tr.section-head td {
      background: #f5f5f5;
      font-size: 9px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      color: #525252;
      border-top: 1px solid #e5e5e5;
    }
    .statement tr.total td {
      font-weight: bold;
      background: #f0f9ff;
      border-top: 1px solid #25A9EB;
    }
    .note {
      font-size: 10px;
      color: #737373;
      margin: 4px 0 12px;
      line-height: 1.45;
    }
    .note.warn { color: #b45309; }
    .footer {
      position: fixed;
      bottom: 12px;
      left: 32px;
      right: 32px;
      border-top: 1px solid #e5e5e5;
      padding-top: 6px;
      font-size: 9px;
      color: #a3a3a3;
      text-align: center;
    }
    .empty {
      text-align: center;
      color: #737373;
      padding: 18px;
      border: 1px dashed #e5e5e5;
    }
    @page {
      margin: 18mm 14mm 18mm;
    }
  </style>
</head>
<body>
  <div class="header">
    <p class="brand">KhShop</p>
    <p class="title">{{ $title ?? 'Report' }}</p>
    @if (!empty($rangeLabel))
      <p class="meta">Period: {{ $rangeLabel }}</p>
    @endif
    @if (!empty($startDate) && !empty($endDate))
      <p class="meta">{{ $startDate }} → {{ $endDate }}</p>
    @endif
    <p class="meta">Generated: {{ $generatedAt ?? now()->format('M j, Y H:i') }}</p>
  </div>

  @yield('content')

  <script type="text/php">
    if (isset($pdf)) {
      $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
      $size = 8;
      $color = [0.64, 0.64, 0.64];
      $pdf->page_text(40, 18, 'KhShop Admin | ' . ($title ?? 'Report'), $font, $size, $color);
      $pdf->page_text(480, 18, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, $size, $color);
    }
  </script>
</body>
</html>
