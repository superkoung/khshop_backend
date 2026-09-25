@extends('reports.layout')

@section('content')
  @php
    $money = function ($v) {
      return '$' . number_format((float) ($v ?? 0), 2);
    };
    $summary = $summary ?? [];
    $customers = $customers ?? collect();
    $growth = $growth ?? [];
    $joined = function ($v) {
      if (!$v) return '—';
      try {
        return \Illuminate\Support\Carbon::parse($v)->format('M j, Y');
      } catch (\Throwable $e) {
        return (string) $v;
      }
    };
  @endphp

  <h2>Summary</h2>
  <table class="kpis">
    <tr>
      <td>
        <p class="kpi-label">New Customers</p>
        <p class="kpi-value">{{ number_format((int) ($summary['new_customers'] ?? 0)) }}</p>
        <p class="meta">Registered in selected range</p>
      </td>
      <td>
        <p class="kpi-label">Returning Customers</p>
        <p class="kpi-value">{{ number_format((int) ($summary['returning_customers'] ?? 0)) }}</p>
        <p class="meta">Ordered in range with prior history</p>
      </td>
      <td>
        <p class="kpi-label">Avg. Order Value</p>
        <p class="kpi-value">{{ $money($summary['avg_order_value'] ?? 0) }}</p>
        <p class="meta">{{ number_format((int) ($summary['total_orders'] ?? 0)) }} valid orders · excl. cancelled</p>
      </td>
      <td>
        <p class="kpi-label">Customer LTV</p>
        @if (!empty($summary['ltv_available']))
          <p class="kpi-value">{{ $money($summary['ltv'] ?? 0) }}</p>
          <p class="meta">Historical revenue per buyer</p>
        @else
          <p class="kpi-value">—</p>
          <p class="meta">{{ $summary['ltv_note'] ?? 'No reliable order history' }}</p>
        @endif
      </td>
    </tr>
  </table>

  @if (!empty($search))
    <p class="note">Search filter: “{{ $search }}”</p>
  @endif

  <h2>Customer Growth (new registrations)</h2>
  @if (empty($growth))
    <p class="empty">No growth data for this period.</p>
  @else
    <table class="data">
      <thead>
        <tr>
          <th>Date</th>
          <th class="text-right">New Customers</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($growth as $g)
          <tr>
            <td>{{ $g['date'] }}</td>
            <td class="text-right">{{ (int) ($g['new_customers'] ?? 0) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <h2>Top Customers ({{ $customers->count() }} rows)</h2>
  @if ($customers->isEmpty())
    <p class="empty">No customers found for the selected filters.</p>
  @else
    <table class="data">
      <thead>
        <tr>
          <th>Customer</th>
          <th>Email</th>
          <th>Joined</th>
          <th class="text-right">Orders</th>
          <th class="text-right">Total Spent</th>
          <th class="text-right">AOV</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($customers as $c)
          <tr>
            <td>{{ $c['name'] }}</td>
            <td>{{ $c['email'] }}</td>
            <td>{{ $joined($c['joined'] ?? null) }}</td>
            <td class="text-right">{{ (int) ($c['orders'] ?? 0) }}</td>
            <td class="text-right">{{ $money($c['totalSpent'] ?? 0) }}</td>
            <td class="text-right">{{ $money($c['averageOrderValue'] ?? 0) }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <p class="note">
    Valid orders exclude cancelled status. Revenue/AOV use net amount — same rules as Sales Report.
    Full filtered dataset (not limited to dashboard page size).
  </p>
@endsection
