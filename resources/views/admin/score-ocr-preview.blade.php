@extends('layouts.app')

@section('title', 'Preview OCR - Panel Admin')

@section('content')
<section class="section active">
    <h2 class="section-title">
        Preview OCR — Semak & Confirm
        @isset($engine)
            <span style="font-size: 0.7em; background: #d1fae5; color: #065f46; padding: 0.25rem 0.5rem; border-radius: 4px; vertical-align: middle;">
                Engine: {{ $engine }}
            </span>
        @endisset
    </h2>

    <div class="admin-tabs" style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin.scores.ocr') }}" class="admin-tab-btn">← Kembali (Upload Lain)</a>
    </div>

    <div class="form-section">
        <h3>📊 Skor Yang Dikesan: <span id="rowCount">{{ count($parsed) }}</span></h3>
        <p style="color: var(--text-light); margin-bottom: 1rem;">
            <i class="fas fa-edit"></i> Awak boleh edit nickname/skor sebelum confirm.
            Baris kosong/salah boleh dipadam.
        </p>

        @if(count($parsed) === 0)
            <div class="error-message">
                <strong>⚠️ Tiada skor dikesan dari OCR.</strong>
                Sila semak raw text di bawah dan tambah baris manual, atau cuba upload semula dengan kualiti lebih baik.
            </div>
        @endif

        <form id="confirmForm">
            @csrf
            <table class="ocr-table" id="scoreTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nickname</th>
                        <th>G1</th>
                        <th>G2</th>
                        <th>G3</th>
                        <th>G4</th>
                        <th>G5</th>
                        <th>Total</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody id="scoreRows">
                    @foreach($parsed as $i => $row)
                        <tr data-idx="{{ $i }}">
                            <td>{{ $i + 1 }}</td>
                            <td><input type="text" name="rows[{{ $i }}][nickname]" value="{{ $row['nickname'] }}" required></td>
                            <td><input type="number" min="0" max="300" name="rows[{{ $i }}][g1]" value="{{ $row['g1'] }}" class="score-input" required></td>
                            <td><input type="number" min="0" max="300" name="rows[{{ $i }}][g2]" value="{{ $row['g2'] }}" class="score-input" required></td>
                            <td><input type="number" min="0" max="300" name="rows[{{ $i }}][g3]" value="{{ $row['g3'] }}" class="score-input" required></td>
                            <td><input type="number" min="0" max="300" name="rows[{{ $i }}][g4]" value="{{ $row['g4'] }}" class="score-input" required></td>
                            <td><input type="number" min="0" max="300" name="rows[{{ $i }}][g5]" value="{{ $row['g5'] }}" class="score-input" required></td>
                            <td class="total-cell">{{ $row['total'] }}</td>
                            <td><button type="button" class="btn-remove" onclick="removeRow(this)">✕</button></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="form-actions" style="margin-top: 1rem;">
                <button type="button" class="btn btn-secondary" onclick="addRow()">
                    <i class="fas fa-plus"></i> Tambah Baris
                </button>
                <button type="submit" class="btn btn-primary" id="confirmBtn">
                    <i class="fas fa-check"></i> Simpan Semua Skor
                </button>
            </div>
        </form>

        <div id="confirmResult" style="margin-top: 1rem; display: none;"></div>
    </div>

    <details class="raw-text-section" style="margin-top: 1.5rem;">
        <summary style="cursor: pointer; padding: 0.5rem; background: #f5f5f5; border-radius: 4px;">
            🔍 Raw OCR Text (debug — klik untuk lihat)
        </summary>
        <pre style="background: #1f2937; color: #d1d5db; padding: 1rem; border-radius: 4px; overflow-x: auto; max-height: 400px; margin-top: 0.5rem;">{{ $rawText }}</pre>
    </details>
</section>
@endsection

@push('styles')
<style>
.ocr-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 1rem;
}
.ocr-table th, .ocr-table td {
    padding: 0.5rem;
    border: 1px solid #e5e7eb;
    text-align: center;
}
.ocr-table th {
    background: #f3f4f6;
    font-weight: 600;
}
.ocr-table input {
    width: 100%;
    padding: 0.35rem;
    border: 1px solid #d1d5db;
    border-radius: 4px;
}
.ocr-table .score-input {
    width: 70px;
    text-align: center;
}
.total-cell {
    font-weight: 600;
    background: #f0fdf4;
}
.btn-remove {
    background: #ef4444;
    color: white;
    border: none;
    width: 28px;
    height: 28px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 1rem;
}
.btn-remove:hover {
    background: #dc2626;
}
.error-message {
    padding: 0.75rem;
    background-color: #fef3c7;
    color: #78350f;
    border-radius: 4px;
    margin-bottom: 1rem;
}
.success-message {
    padding: 0.75rem;
    background-color: #d4edda;
    color: #155724;
    border-radius: 4px;
}
</style>
@endpush

@push('scripts')
<script>
let rowIdx = {{ count($parsed) }};

function recalcTotal(row) {
    const inputs = row.querySelectorAll('.score-input');
    let total = 0;
    inputs.forEach(i => total += parseInt(i.value) || 0);
    row.querySelector('.total-cell').textContent = total;
}

function removeRow(btn) {
    btn.closest('tr').remove();
    document.getElementById('rowCount').textContent = document.querySelectorAll('#scoreRows tr').length;
}

function addRow() {
    const tbody = document.getElementById('scoreRows');
    const tr = document.createElement('tr');
    tr.dataset.idx = rowIdx;
    tr.innerHTML = `
        <td>${tbody.children.length + 1}</td>
        <td><input type="text" name="rows[${rowIdx}][nickname]" required></td>
        <td><input type="number" min="0" max="300" name="rows[${rowIdx}][g1]" value="0" class="score-input" required></td>
        <td><input type="number" min="0" max="300" name="rows[${rowIdx}][g2]" value="0" class="score-input" required></td>
        <td><input type="number" min="0" max="300" name="rows[${rowIdx}][g3]" value="0" class="score-input" required></td>
        <td><input type="number" min="0" max="300" name="rows[${rowIdx}][g4]" value="0" class="score-input" required></td>
        <td><input type="number" min="0" max="300" name="rows[${rowIdx}][g5]" value="0" class="score-input" required></td>
        <td class="total-cell">0</td>
        <td><button type="button" class="btn-remove" onclick="removeRow(this)">✕</button></td>
    `;
    tbody.appendChild(tr);
    rowIdx++;
    document.getElementById('rowCount').textContent = tbody.children.length;
}

// Live total recalc
document.addEventListener('input', function(e) {
    if (e.target.classList.contains('score-input')) {
        recalcTotal(e.target.closest('tr'));
    }
});

document.getElementById('confirmForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    const btn = document.getElementById('confirmBtn');
    const result = document.getElementById('confirmResult');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menyimpan...';

    const formData = new FormData(this);

    try {
        const resp = await fetch('{{ route('admin.scores.ocr.confirm') }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const data = await resp.json();

        if (data.success) {
            result.style.display = 'block';
            result.className = 'success-message';
            result.innerHTML = `
                ✅ ${data.message}
                ${data.unmatched > 0 ? '<br><a href="{{ route('admin.scores.unmatched') }}" class="btn btn-sm btn-primary" style="margin-top: 0.5rem;"><i class="fas fa-clipboard-check"></i> Semak yang belum dipadan</a>' : ''}
            `;
            btn.style.display = 'none';
        } else {
            result.style.display = 'block';
            result.className = 'error-message';
            result.innerHTML = `❌ ${data.message || 'Ralat menyimpan'}`;
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Simpan Semua Skor';
        }
    } catch (err) {
        result.style.display = 'block';
        result.className = 'error-message';
        result.innerHTML = `❌ Ralat rangkaian: ${err.message}`;
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Simpan Semua Skor';
    }
});
</script>
@endpush
