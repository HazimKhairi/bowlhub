@extends('layouts.app')

@section('title', 'Import Skor dari PDF/Foto - Panel Admin')

@section('content')
<section class="section active">
    <h2 class="section-title">Import Skor dari PDF / Foto</h2>

    <div class="admin-tabs" style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin') }}" class="admin-tab-btn">Kembali ke Panel</a>
        <a href="{{ route('admin.scores.import') }}" class="admin-tab-btn">
            <i class="fas fa-file-excel"></i> Import Excel
        </a>
        <a href="{{ route('admin.scores.unmatched') }}" class="admin-tab-btn">Belum Dipadan</a>
    </div>

    @if(session('error'))
        <div class="error-message" style="margin-bottom: 1rem;">{{ session('error') }}</div>
    @endif

    <div class="form-section">
        <h3>Muat Naik PDF / Imej Skor</h3>
        <p style="margin-bottom: 1rem; color: var(--text-light);">
            <i class="fas fa-info-circle"></i>
            Sistem akan baca skor dari fail menggunakan OCR (Tesseract). Format yang disokong:
            <strong>PDF, JPG, PNG</strong> (max 10MB).
            Lepas baca, awak boleh semak & kemaskini sebelum simpan.
        </p>

        <div class="hint-box">
            <strong>💡 Tip untuk OCR yang tepat:</strong>
            <ul>
                <li>Gunakan PDF asal (printout) berbanding photo skrin</li>
                <li>Pastikan teks jelas, tidak kabur</li>
                <li>Format ideal: <code>nickname g1 g2 g3 g4 g5</code> dalam satu baris</li>
                <li>Tulisan tangan: accuracy lebih rendah, semak setiap baris sebelum confirm</li>
            </ul>
        </div>

        <form method="POST" action="{{ route('admin.scores.ocr.preview') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="ocrFile">Pilih Fail (PDF / JPG / PNG)</label>
                <input type="file" id="ocrFile" name="file" accept=".pdf,.jpg,.jpeg,.png" required>
                <small class="form-hint">Max 10MB. PDF auto-convert ke imej beresolusi tinggi (300 DPI).</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-magic"></i> Proses dengan OCR
                </button>
            </div>
        </form>
    </div>
</section>
@endsection

@push('styles')
<style>
.hint-box {
    background: #e0f2fe;
    border-left: 4px solid #0284c7;
    padding: 0.75rem 1rem;
    margin: 1rem 0;
    border-radius: 4px;
    font-size: 0.9rem;
}
.hint-box ul {
    margin: 0.5rem 0 0 1.25rem;
    padding: 0;
}
.hint-box code {
    background: rgba(0,0,0,0.05);
    padding: 0.1rem 0.35rem;
    border-radius: 3px;
    font-size: 0.85em;
}
.error-message {
    padding: 0.75rem;
    background-color: #f8d7da;
    color: #721c24;
    border-radius: 4px;
}
</style>
@endpush

@push('scripts')
<script>
document.querySelector('form').addEventListener('submit', function() {
    const btn = document.getElementById('submitBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses OCR... (5-30 saat)';
});
</script>
@endpush
