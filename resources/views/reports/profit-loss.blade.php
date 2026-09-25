@extends('reports.layout')

@section('content')
  @php
    $money = function ($v) {
      return '$' . number_format((float) ($v ?? 0), 2);
    };
    $summary = $summary ?? [];
    $statement = $statement ?? [];
    $comparison = $comparison ?? null;
    $prev = $comparison['summary'] ?? null;
  @endphp

  <h2>Summary</h2>
  <table class="kpis">
    <tr>
      <td>
        <p class="kpi-label">Total Revenue</p>
        <p class="kpi-value">{{ $money($summary['revenue'] ?? 0) }}</p>
        @if ($prev)
          <p class="meta">Prev: {{ $money($prev['revenue'] ?? 0) }}</p>
        @endif
      </td>
      <td>
        <p class="kpi-label">COGS</p>
        @if (!empty($summary['cogs_available']))
          <p class="kpi-value">{{ $money($summary['cogs'] ?? 0) }}</p>
        @else
          <p class="kpi-value">{{ $money(0) }}</p>
          <p class="meta">Unavailable</p>
        @endif
      </td>
      <td>
        <p class="kpi-label">Gross Profit</p>
        <p class="kpi-value">{{ $money($summary['gross_profit'] ?? 0) }}</p>
        @if (($summary['revenue'] ?? 0) > 0)
          <p class="meta">Margin {{ $summary['gross_margin'] ?? 0 }}%</p>
        @endif
      </td>
      <td>
        <p class="kpi-label">Operating Expenses</p>
        @if (!empty($summary['expenses_available']))
          <p class="kpi-value">{{ $money($summary['operating_expenses'] ?? 0) }}</p>
        @else
          <p class="kpi-value">{{ $money(0) }}</p>
          <p class="meta">No expense data</p>
        @endif
      </td>
      <td>
        <p class="kpi-label">Net Profit / Loss</p>
        <p class="kpi-value">{{ $money($summary['net_profit'] ?? 0) }}</p>
      </td>
    </tr>
  </table>

  @if (empty($summary['cogs_available']))
    <p class="note warn">
      Product cost data is not available for all sold items — COGS and Gross Profit may be incomplete.
    </p>
  @endif
  @if (empty($summary['expenses_available']))
    <p class="note">
      Operating expenses require an expense data source (none exists in this project yet).
    </p>
  @endif

  @if ($comparison && $prev)
    <p class="note">
      Comparing with previous period: {{ $comparison['range_label'] }}
      ({{ $comparison['start_date'] }} → {{ $comparison['end_date'] }})
    </p>
    <table class="data">
      <thead>
        <tr>
          <th>Metric</th>
          <th class="text-right">Current</th>
          <th class="text-right">Previous</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Revenue</td>
          <td class="text-right">{{ $money($summary['revenue'] ?? 0) }}</td>
          <td class="text-right">{{ $money($prev['revenue'] ?? 0) }}</td>
        </tr>
        <tr>
          <td>COGS</td>
          <td class="text-right">{{ !empty($summary['cogs_available']) ? $money($summary['cogs'] ?? 0) : $money(0) . ' (n/a)' }}</td>
          <td class="text-right">{{ !empty($prev['cogs_available']) ? $money($prev['cogs'] ?? 0) : $money(0) . ' (n/a)' }}</td>
        </tr>
        <tr>
          <td>Gross Profit</td>
          <td class="text-right">{{ $money($summary['gross_profit'] ?? 0) }}</td>
          <td class="text-right">{{ $money($prev['gross_profit'] ?? 0) }}</td>
        </tr>
        <tr>
          <td>Net Profit / Loss</td>
          <td class="text-right">{{ $money($summary['net_profit'] ?? 0) }}</td>
          <td class="text-right">{{ $money($prev['net_profit'] ?? 0) }}</td>
        </tr>
      </tbody>
    </table>
  @endif

  <h2>Detailed P&amp;L Statement</h2>
  <table class="statement">
    <tr class="section-head"><td colspan="2">Revenue</td></tr>
    <tr>
      <td>Gross Revenue</td>
      <td class="text-right">{{ $money($statement['revenue']['gross_revenue'] ?? 0) }}</td>
    </tr>
    <tr>
      <td>Discounts / Coupons</td>
      <td class="text-right">{{ $money($statement['revenue']['discounts'] ?? 0) }}</td>
    </tr>
    <tr>
      <td>Returns / Refunds</td>
      <td class="text-right">{{ $money($statement['revenue']['returns'] ?? 0) }}</td>
    </tr>
    <tr class="total">
      <td>Net Revenue</td>
      <td class="text-right">{{ $money($statement['revenue']['net_revenue'] ?? 0) }}</td>
    </tr>

    <tr class="section-head"><td colspan="2">Cost of Goods Sold</td></tr>
    <tr>
      <td>Product Cost / COGS</td>
      <td class="text-right">
        @if (!empty($statement['cogs']['available']))
          {{ $money($statement['cogs']['amount'] ?? 0) }}
        @else
          {{ $money(0) }} (cost data unavailable)
        @endif
      </td>
    </tr>

    <tr class="section-head"><td colspan="2">Gross Profit</td></tr>
    <tr>
      <td>Gross Profit</td>
      <td class="text-right">{{ $money($statement['gross_profit']['amount'] ?? 0) }}</td>
    </tr>
    <tr>
      <td>Gross Margin %</td>
      <td class="text-right">{{ $statement['gross_profit']['margin'] ?? 0 }}%</td>
    </tr>

    <tr class="section-head"><td colspan="2">Operating Expenses</td></tr>
    <tr>
      <td>Operating Expenses</td>
      <td class="text-right">
        @if (!empty($statement['operating_expenses']['available']))
          {{ $money($statement['operating_expenses']['amount'] ?? 0) }}
        @else
          {{ $money(0) }} (no expense data available)
        @endif
      </td>
    </tr>
    <tr class="total">
      <td>Total Operating Expenses</td>
      <td class="text-right">{{ $money($statement['operating_expenses']['amount'] ?? 0) }}</td>
    </tr>

    <tr class="section-head"><td colspan="2">Net Profit / Loss</td></tr>
    <tr class="total">
      <td>Net Profit / Loss</td>
      <td class="text-right">{{ $money($statement['net_profit']['amount'] ?? 0) }}</td>
    </tr>
  </table>

  <p class="note">
    Orders: {{ number_format((int) ($summary['orders'] ?? 0)) }} ·
    Units sold: {{ number_format((int) ($summary['units_sold'] ?? 0)) }} ·
    Cancelled orders excluded · COGS from primary supplier cost price when available ·
    Operating expenses unavailable (no expense data source).
  </p>
@endsection
