{{-- Tab Documents --}}
@php
use Illuminate\Support\Facades\Storage;
@endphp
<div class="bg-white rounded-xl border border-gray-200 p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-semibold text-gray-800 flex items-center">
            <i class="fa-solid fa-folder mr-2 text-indigo-600"></i>
            Dokumen
        </h3>

        @if($canEditEmployee)
        <button type="button" onclick="document.getElementById('uploadModal').classList.remove('hidden')"
            class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
            <i class="fa-solid fa-upload mr-2"></i>
            Upload Dokumen
        </button>
        @endif
    </div>

    @if($employee->documents->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($employee->documents as $doc)
        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center flex-shrink-0">
                    <i class="fa-solid fa-file-pdf text-red-600"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <h4 class="font-medium text-gray-900 truncate">{{ $doc->title }}</h4>
                    <p class="text-xs text-gray-500">{{ $doc->document_type_label ?? $doc->document_type }}</p>
                    @if($doc->issued_date)
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $doc->issued_date->format('d M Y') }}
                        @if($doc->expiry_date)
                            - {{ $doc->expiry_date->format('d M Y') }}
                        @endif
                    </p>
                    @endif
                    @if($doc->is_expired)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700 mt-1">
                        <i class="fa-solid fa-exclamation-circle mr-1"></i> Expired
                    </span>
                    @elseif($doc->is_expiring_soon)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-700 mt-1">
                        <i class="fa-solid fa-clock mr-1"></i> Expiring Soon
                    </span>
                    @endif
                </div>
            </div>
            <div class="mt-3 flex gap-2">
                @if(Storage::exists($doc->file_path))
                <a href="{{ Storage::url($doc->file_path) }}" target="_blank"
                    class="flex-1 text-center px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 text-sm">
                    <i class="fa-solid fa-eye mr-1"></i> Lihat
                </a>
                <a href="{{ Storage::url($doc->file_path) }}" download="{{ $doc->file_name }}"
                    class="flex-1 text-center px-3 py-1.5 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm">
                    <i class="fa-solid fa-download mr-1"></i> Unduh
                </a>
                @else
                <span class="text-xs text-red-500">File tidak ditemukan</span>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    @else
    <div class="text-center py-12">
        <div class="w-16 h-16 mx-auto rounded-full bg-gray-100 flex items-center justify-center mb-4">
            <i class="fa-solid fa-folder-open text-3xl text-gray-300"></i>
        </div>
        <h4 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Dokumen</h4>
        <p class="text-gray-500 mb-4">Dokumen karyawan seperti KTP, CV, kontrak, dll belum diupload.</p>
    </div>
    @endif
</div>

{{-- Upload Modal --}}
<div id="uploadModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="document.getElementById('uploadModal').classList.add('hidden')"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-white rounded-xl shadow-xl">
        <div class="p-6 border-b">
            <h3 class="text-lg font-semibold">Upload Dokumen</h3>
        </div>
        <form action="{{ route('administrasi.data_karyawan.document', $employee->id) }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipe Dokumen</label>
                <select name="document_type" class="w-full px-4 py-2 border rounded-lg" required>
                    <option value="">Pilih Tipe</option>
                    <option value="ktp">KTP</option>
                    <option value="kk">Kartu Keluarga</option>
                    <option value="cv">CV / Resume</option>
                    <option value="contract">Kontrak Kerja</option>
                    <option value="certificate">Sertifikat</option>
                    <option value="other">Lainnya</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Judul Dokumen</label>
                <input type="text" name="title" class="w-full px-4 py-2 border rounded-lg" required placeholder="Contoh: KTP John Doe">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">File</label>
                <input type="file" name="file" class="w-full px-4 py-2 border rounded-lg" required accept=".pdf,.jpg,.jpeg,.png">
                <p class="mt-1 text-xs text-gray-500">Format: PDF, JPG, PNG. Maksimal 10MB</p>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="button" onclick="document.getElementById('uploadModal').classList.add('hidden')"
                    class="flex-1 px-4 py-2 border rounded-lg hover:bg-gray-50">
                    Batal
                </button>
                <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    Upload
                </button>
            </div>
        </form>
    </div>
</div>
