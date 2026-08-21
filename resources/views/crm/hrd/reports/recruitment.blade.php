@extends('layouts.app')

@section('title', 'Laporan Rekrutmen')

@section('content')
<div class="page-header">
    <h3 class="page-title">Laporan Rekrutmen</h3>
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

                <h5>Pipeline Rekrutmen</h5>
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card bg-primary text-white">
                            <div class="card-body text-center">
                                <h5>{{ $pipeline['applied'] }}</h5>
                                <p>Melamar</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-info text-white">
                            <div class="card-body text-center">
                                <h5>{{ $pipeline['screening'] }}</h5>
                                <p>Seleksi</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-warning text-white">
                            <div class="card-body text-center">
                                <h5>{{ $pipeline['interview'] }}</h5>
                                <p>Wawancara</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-secondary text-white">
                            <div class="card-body text-center">
                                <h5>{{ $pipeline['offering'] }}</h5>
                                <p>Penawaran</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-success text-white">
                            <div class="card-body text-center">
                                <h5>{{ $pipeline['hired'] }}</h5>
                                <p>Diterima</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-danger text-white">
                            <div class="card-body text-center">
                                <h5>{{ $pipeline['rejected'] }}</h5>
                                <p>Ditolak</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama Kandidat</th>
                                <th>Posisi</th>
                                <th>Email</th>
                                <th>Sumber</th>
                                <th>Tahap</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recruitments as $rec)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $rec->candidate_name }}</td>
                                <td>{{ $rec->position?->name ?? '-' }}</td>
                                <td>{{ $rec->email }}</td>
                                <td>{{ ucfirst($rec->source) }}</td>
                                <td>
                                    <span class="badge bg-{{ $rec->stage === 'hiring' ? 'success' : ($rec->stage === 'rejected' ? 'danger' : 'info') }}">
                                        {{ str_replace('_', ' ', ucfirst($rec->stage)) }}
                                    </span>
                                </td>
                                <td>{{ $rec->created_at->format('d/m/Y') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada data rekrutmen</td>
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
