@extends('layouts.app')

@section('title', 'Laporan Pelatihan')

@section('content')
<div class="page-header">
    <h3 class="page-title">Laporan Pelatihan</h3>
</div>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label>Tahun</label>
                            <select name="year" class="form-control">
                                @for($y = date('Y') - 2; $y <= date('Y'); $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
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

                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5>{{ $stats['total'] }}</h5>
                                <p>Total Pelatihan</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5>{{ $stats['completed'] }}</h5>
                                <p>Selesai</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5>{{ $stats['total_participants'] }}</h5>
                                <p>Total Peserta</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5>Rp {{ number_format($stats['total_cost'], 0) }}</h5>
                                <p>Total Biaya</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Judul</th>
                                <th>Tipe</th>
                                <th>Tanggal</th>
                                <th>Durasi</th>
                                <th>Peserta</th>
                                <th>Biaya</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trainings as $training)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $training->title }}</td>
                                <td>{{ ucfirst($training->type) }}</td>
                                <td>{{ $training->start_date->format('d/m/Y') }} - {{ $training->end_date->format('d/m/Y') }}</td>
                                <td>{{ $training->duration_hours }} jam</td>
                                <td>{{ $training->participants_count }}</td>
                                <td>Rp {{ number_format($training->cost, 0) }}</td>
                                <td>
                                    <span class="badge bg-{{ $training->status === 'completed' ? 'success' : ($training->status === 'ongoing' ? 'info' : 'warning') }}">
                                        {{ ucfirst($training->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted">Tidak ada data pelatihan</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Filter Info --}}
                <div class="mt-3 p-3 bg-light border-top text-end">
                    @php
                        $hasFilters = !empty($year);
                        $filterInfo = $hasFilters ? ['Tahun: ' . $year] : [];
                    @endphp
                    @if($hasFilters)
                        <small class="text-muted">
                            <strong>Filter:</strong>
                            @foreach($filterInfo as $info)
                                <span class="ms-2">{{ $info }}</span>
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
