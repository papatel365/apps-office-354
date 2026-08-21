@extends('layouts.app')

@section('title', 'Laporan Gaji')

@section('content')
<div class="page-header">
    <h3 class="page-title">Laporan Gaji</h3>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-2">
                            <label>Bulan Mulai</label>
                            <select name="start_month" class="form-control">
                                @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $startMonth == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->format('F') }}
                                </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Tahun Mulai</label>
                            <select name="start_year" class="form-control">
                                @for($y = date('Y') - 2; $y <= date('Y'); $y++)
                                <option value="{{ $y }}" {{ $startYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Bulan Akhir</label>
                            <select name="end_month" class="form-control">
                                @for($i = 1; $i <= 12; $i++)
                                <option value="{{ $i }}" {{ $endMonth == $i ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($i)->format('F') }}
                                </option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Tahun Akhir</label>
                            <select name="end_year" class="form-control">
                                @for($y = date('Y') - 2; $y <= date('Y'); $y++)
                                <option value="{{ $y }}" {{ $endYear == $y ? 'selected' : '' }}>{{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="mdi mdi-filter"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <h5>Ringkasan Bulanan</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Periode</th>
                                <th>Jumlah Karyawan</th>
                                <th>Total Gaji Bruto</th>
                                <th>Total Potongan</th>
                                <th>Total Gaji Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($monthlySummary as $summary)
                            <tr>
                                <td>{{ $summary['period'] }}</td>
                                <td>{{ $summary['count'] }}</td>
                                <td>Rp {{ number_format($summary['total_gross'], 0) }}</td>
                                <td>Rp {{ number_format($summary['total_deductions'], 0) }}</td>
                                <td>Rp {{ number_format($summary['total_net'], 0) }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Filter Info --}}
                <div class="mt-3 p-3 bg-light border-top text-end">
                    @php
                        $hasFilters = !empty($startMonth) || !empty($startYear) || !empty($endMonth) || !empty($endYear);
                        $filterInfo = [];
                        if ($startMonth && $startYear) {
                            $filterInfo[] = 'Mulai: ' . \Carbon\Carbon::create($startYear, $startMonth, 1)->format('F Y');
                        }
                        if ($endMonth && $endYear) {
                            $filterInfo[] = 'Selesai: ' . \Carbon\Carbon::create($endYear, $endMonth, 1)->format('F Y');
                        }
                    @endphp
                    @if($hasFilters)
                        <small class="text-muted">
                            <strong>Filter:</strong>
                            @foreach($filterInfo as $info)
                                <span class="ms-2">{{ $info }}</span>
                                @if(!$loop->last)<span class="mx-1">|</span>@endif
                            @endforeach
                        </small>
                    @else
                        <small class="text-muted">
                            <strong>Filter:</strong> Tidak menggunakan filter (Seluruh Data)
                        </small>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
