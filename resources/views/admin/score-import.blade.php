@extends('layouts.app')

@section('title', 'Import Skor - Panel Admin')

@section('content')
<section class="section active">
    <h2 class="section-title">Import Skor</h2>

    <div class="admin-tabs" style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin') }}" class="admin-tab-btn">Kembali ke Panel</a>
        <a href="{{ route('admin.scores.ocr') }}" class="admin-tab-btn">
            <i class="fas fa-camera"></i> Import dari PDF/Foto (OCR)
        </a>
        <a href="{{ route('admin.scores.unmatched') }}" class="admin-tab-btn">
            Belum Dipadan
            @if($unmatchedCount > 0)
                <span class="badge-count">{{ $unmatchedCount }}</span>
            @endif
        </a>
    </div>

    <div class="form-section">
        <h3>Muat Naik Senarai Skor</h3>
        <p style="margin-bottom: 1rem; color: var(--text-light);">
            <i class="fas fa-info-circle"></i>
            Sistem akan padankan setiap baris dengan peserta berdasarkan <strong>nickname</strong>.
            Skor yang tidak dipadan akan disimpan untuk semakan manual.
        </p>

        <div class="template-buttons">
            <a href="{{ route('admin.template.download', 'score-import') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-download"></i> Muat Turun Template Score Import
            </a>
        </div>

        <form id="scoreImportForm" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label for="scoreFile">Pilih Fail Excel</label>
                <input type="file" id="scoreFile" name="file" accept=".xlsx,.xls" required>
                <small class="form-hint">Format: nickname, g1, g2, g3, g4, g5</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-upload"></i> Import Skor
                </button>
            </div>
        </form>

        <div id="importResults" class="import-results" style="display: none;"></div>
        <div id="importErrors" class="error-messages"></div>
    </div>
</section>
@endsection

@push('styles')
<style>
.badge-count {
    display: inline-block;
    background: var(--danger-color);
    color: white;
    font-size: 0.75rem;
    font-weight: 700;
    padding: 0.15rem 0.5rem;
    border-radius: 999px;
    margin-left: 0.4rem;
    vertical-align: middle;
}

.import-results {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 8px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.import-results.partial {
    background-color: #fff3cd;
    border-color: #ffeeba;
    color: #856404;
}

.error-messages {
    margin-top: 1rem;
}

.error-message {
    padding: 0.75rem;
    background-color: #f8d7da;
    color: #721c24;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

.result-summary {
    display: flex;
    gap: 1rem;
    margin-bottom: 0.5rem;
}

.result-item {
    flex: 1;
    text-align: center;
    padding: 0.75rem;
    background: rgba(255, 255, 255, 0.5);
    border-radius: 6px;
}

.result-count {
    font-size: 1.75rem;
    font-weight: 700;
}

.result-label {
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.template-buttons {
    margin-bottom: 1.5rem;
}
</style>
@endpush

@push('scripts')
<script>
document.getElementById('scoreImportForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const submitBtn = document.getElementById('submitBtn');
    const resultsDiv = document.getElementById('importResults');
    const errorsDiv = document.getElementById('importErrors');
    const file = document.getElementById('scoreFile').files[0];

    if (!file) {
        alert('Sila pilih fail Excel');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';
    resultsDiv.style.display = 'none';
    errorsDiv.innerHTML = '';

    const formData = new FormData();
    formData.append('file', file);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

    try {
        const response = await fetch('{{ route('admin.scores.import.submit') }}', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            const r = result.results;
            const hasUnmatched = r.unmatched > 0 || r.invalid > 0;

            resultsDiv.className = hasUnmatched ? 'import-results partial' : 'import-results';
            resultsDiv.style.display = 'block';
            resultsDiv.innerHTML = `
                <div class="result-summary">
                    <div class="result-item">
                        <div class="result-count" style="color: #10b981;">${r.matched}</div>
                        <div class="result-label">Dipadan & Disimpan</div>
                    </div>
                    <div class="result-item">
                        <div class="result-count" style="color: #f59e0b;">${r.unmatched}</div>
                        <div class="result-label">Perlu Semakan Manual</div>
                    </div>
                    <div class="result-item">
                        <div class="result-count" style="color: #ef4444;">${r.invalid}</div>
                        <div class="result-label">Tidak Sah</div>
                    </div>
                </div>
                <p style="margin-top: 0.75rem;">${result.message}</p>
                ${r.unmatched > 0 ? `<p style="margin-top: 0.5rem;"><a href="{{ route('admin.scores.unmatched') }}" class="btn btn-sm btn-primary"><i class="fas fa-clipboard-check"></i> Semak Manual</a></p>` : ''}
            `;
        }

        if (result.errors && result.errors.length > 0) {
            errorsDiv.innerHTML = result.errors.map(err => `<div class="error-message">${err}</div>`).join('');
        }

        if (!result.success && result.message) {
            errorsDiv.innerHTML += `<div class="error-message">${result.message}</div>`;
        }

        document.getElementById('scoreImportForm').reset();
    } catch (error) {
        errorsDiv.innerHTML = `<div class="error-message">Ralat rangkaian: ${error.message}</div>`;
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-upload"></i> Import Skor';
    }
});
</script>
@endpush
