@extends('layouts.app')

@section('title', 'Laporan Cuti')

@section('content')
<div class="page-header">
    <h3 class="page-title">Laporan Cuti</h3>
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

                <h5>Ringkasan per Jenis Cuti</h5>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Jenis Cuti</th>
                                <th>Jumlah Pengajuan</th>
                                <th>Total Hari</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($byType as $type)
                            <tr>
                                <td>{{ $type->name }}</td>
                                <td>{{ $type->leaves_count }}</td>
                                <td>{{ $type->leaves->sum('total_days') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <h5>Daftar Cuti Karyawan</h5>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Karyawan</th>
                                <th>Jenis Cuti</th>
                                <th>Tanggal Mulai</th>
                                <th>Tanggal Selesai</th>
                                <th>Jumlah Hari</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($leaves as $leave)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $leave->employee?->full_name ?? '-' }}</td>
                                <td>{{ $leave->leaveType?->name ?? '-' }}</td>
                                <td>{{ $leave->start_date->format('d/m/Y') }}</td>
                                <td>{{ $leave->end_date->format('d/m/Y') }}</td>
                                <td>{{ $leave->total_days }}</td>
                                <td>
                                    <span class="badge bg-{{ $leave->status === 'approved' ? 'success' : ($leave->status === 'pending' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($leave->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada data cuti</td>
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
