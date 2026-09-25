@extends('reports.layout')

@section('content')
  @php
    $money = function ($v) {
      return '$' . number_format((float) ($v ?? 0), 2);
    };
    $summary = $summary ?? [];
    $products = $products ?? collect();
    $tabLabels = [
      'best' => 'Best Sellers',
      'worst' => 'Worst Sellers',
      'out_of_stock' => 'Out of Stock',
    ];
    $tabLabel = $tabLabels[$tab ?? 'best'] ?? ($tab ?? 'Products');
    $isOutOfStock = ($tab ?? 'best') === 'out_of_stock';
    $topCategory = $summary['top_category'] ?? null;
  @endphp

  <h2>Summary · {{ $tabLabel }}</h2>
  <table class="kpis">
    <tr>
      <td>
        <p class="kpi-label">Total Products Sold</p>
        <p class="kpi-value">{{ number_format((int) ($summary['total_units_sold'] ?? 0)) }}</p>
      </td>
      <td>
        <p class="kpi-label">Total Revenue</p>
        <p class="kpi-value">{{ $money($summary['total_revenue'] ?? 0) }}</p>
      </td>
      <td>
        <p class="kpi-label">Top Performing Category</p>
        <p class="kpi-value">{{ $topCategory['name'] ?? '—' }}</p>
        @if ($topCategory)
          <p class="meta">{{ $money($topCategory['revenue'] ?? 0) }} · {{ (int) ($topCategory['units'] ?? 0) }} units</p>
        @else
          <p class="meta">No sales in range</p>
        @endif
      </td>
    </tr>
  </table>

  <h2>{{ $tabLabel }} ({{ $products->count() }} rows)</h2>
  @if ($products->isEmpty())
    <p class="empty">No products found for the selected filters.</p>
  @else
    <table class="data">
      <thead>
        <tr>
          <th>Product</th>
          <th>Category</th>
          <th class="text-right">Units Sold</th>
          <th class="text-right">Revenue</th>
          <th class="text-right">Stock</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($products as $p)
          <tr>
            <td>{{ $p['name'] }}</td>
            <td>{{ $p['category'] }}</td>
            <td class="text-right">{{ $isOutOfStock ? '—' : (int) $p['units_sold'] }}</td>
            <td class="text-right">{{ $isOutOfStock ? '—' : $money($p['revenue']) }}</td>
            <td class="text-right {{ ((int) $p['stock']) === 0 ? 'bold' : '' }}">{{ (int) $p['stock'] }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  <p class="note">
    Sales exclude cancelled orders. Out-of-stock uses current variant stock (not historical sales).
    Full filtered dataset (not limited to dashboard page size).
  </p>
@endsection
