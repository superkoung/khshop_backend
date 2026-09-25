@extends('reports.layout')

@section('content')
  @php
    $money = function ($v) {
      return '$' . number_format((float) ($v ?? 0), 2);
    };
  @endphp

  <h2>Summary</h2>
  <table class="kpis">
    <tr>
      <td>
        <p class="kpi-label">Revenue</p>
        <p class="kpi-value">{{ $money($summary['revenue'] ?? 0) }}</p>
      </td>
      <td>
        <p class="kpi-label">Orders</p>
        <p class="kpi-value">{{ number_format((int) ($summary['orders'] ?? 0)) }}</p>
      </td>
      <td>
        <p class="kpi-label">Avg. Order Value</p>
        <p class="kpi-value">{{ $money($summary['avgOrder'] ?? 0) }}</p>
      </td>
      <td>
        <p class="kpi-label">Customers</p>
        <p class="kpi-value">{{ number_format((int) ($summary['customers'] ?? 0)) }}</p>
      </td>
    </tr>
  </table>

  <h2>Trend ({{ $chartPeriod ?? 'chart' }})</h2>
  @if (empty($chart))
    <p class="empty">No sales in this period.</p>
  @else
    <table class="data">
      <thead>
        <tr>
          <th>Date</th>
          <th class="text-right">Revenue</th>
          <th class="text-right">Orders</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($chart as $row)
          <tr>
            <td>{{ $row['date'] }}</td>
            <td class="text-right">{{ $money($row['sales'] ?? 0) }}</td>
            <td class="text-right">{{ (int) ($row['orders'] ?? 0) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <p class="note">
    Sales figures exclude cancelled orders. Revenue uses order net amount — same rules as the Sales Report dashboard.
  </p>
@endsection
