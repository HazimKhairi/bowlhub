@extends('layouts.app')

@section('title', 'Skor Belum Dipadan - Panel Admin')

@section('content')
<section class="section active">
    <h2 class="section-title">Skor Belum Dipadan</h2>

    <div class="admin-tabs" style="margin-bottom: 1.5rem;">
        <a href="{{ route('admin') }}" class="admin-tab-btn">Kembali ke Panel</a>
        <a href="{{ route('admin.scores.import') }}" class="admin-tab-btn">Import Skor Baharu</a>
    </div>

    @if($unmatched->isEmpty())
        <div class="empty-state" style="padding: 3rem; text-align: center;">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--success-color);"></i>
            <p style="margin-top: 1rem; color: var(--text-light);">Tiada skor belum dipadan. Semua dah selesai!</p>
        </div>
    @else
        <p style="margin-bottom: 1rem; color: var(--text-light);">
            <i class="fas fa-info-circle"></i>
            {{ $unmatched->count() }} skor memerlukan semakan manual. Pilih peserta yang betul untuk setiap rekod, atau buang jika tidak relevan.
        </p>

        <div class="unmatched-list">
            @foreach($unmatched as $row)
                <div class="unmatched-card" id="row-{{ $row->id }}">
                    <div class="unmatched-info">
                        <h4>
                            <i class="fas fa-user-question"></i>
                            Nickname: <span class="nickname-tag">{{ $row->nickname }}</span>
                        </h4>
                        <p class="reason-tag reason-{{ $row->reason }}">
                            @if($row->reason === 'no_match')
                                Tiada padanan dalam database
                            @elseif($row->reason === 'multiple_matches')
                                {{ count($row->match_candidates ?? []) }} padanan dijumpai (perlu pilih satu)
                            @else
                                Data tidak sah
                            @endif
                        </p>

                        <div class="score-preview">
                            <span>G1: <strong>{{ $row->g1 }}</strong></span>
                            <span>G2: <strong>{{ $row->g2 }}</strong></span>
                            <span>G3: <strong>{{ $row->g3 }}</strong></span>
                            <span>G4: <strong>{{ $row->g4 }}</strong></span>
                            <span>G5: <strong>{{ $row->g5 }}</strong></span>
                            <span class="total">Jumlah: <strong>{{ $row->total }}</strong></span>
                        </div>

                        @if($row->row_number)
                            <small style="color: var(--text-light);">Baris Excel asal: {{ $row->row_number }}</small>
                        @endif
                    </div>

                    <div class="unmatched-actions">
                        <select class="participant-picker" data-id="{{ $row->id }}">
                            <option value="">— Pilih peserta —</option>
                            @if(! empty($row->match_candidates))
                                <optgroup label="Padanan dicadangkan">
                                    @foreach($row->match_candidates as $c)
                                        <option value="{{ $c['id'] }}">{{ $c['name'] }} ({{ $c['nickname'] ?? '-' }}) — {{ $c['team'] }} [{{ $c['event_type'] }}]</option>
                                    @endforeach
                                </optgroup>
                            @endif
                            <optgroup label="Semua peserta">
                                @foreach($participants as $p)
                                    <option value="{{ $p->id }}">
                                        {{ $p->name }}
                                        @if($p->nickname) ({{ $p->nickname }}) @endif
                                        — {{ $p->team }} [{{ $p->event_type }}]
                                    </option>
                                @endforeach
                            </optgroup>
                        </select>

                        <button class="btn btn-sm btn-primary" onclick="resolveRow({{ $row->id }})">
                            <i class="fas fa-link"></i> Padankan
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="discardRow({{ $row->id }})">
                            <i class="fas fa-trash"></i> Buang
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>
@endsection

@push('styles')
<style>
.unmatched-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.unmatched-card {
    background: white;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    padding: 1.25rem;
    box-shadow: var(--shadow-sm);
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1.5rem;
    align-items: start;
}

.unmatched-info h4 {
    color: var(--text-dark);
    margin-bottom: 0.5rem;
    font-size: 1.05rem;
}

.nickname-tag {
    background: var(--accent-color);
    color: white;
    padding: 0.15rem 0.6rem;
    border-radius: 4px;
    font-family: monospace;
    font-weight: 600;
}

.reason-tag {
    display: inline-block;
    padding: 0.2rem 0.6rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.reason-no_match {
    background: #fee2e2;
    color: #991b1b;
}

.reason-multiple_matches {
    background: #fef3c7;
    color: #92400e;
}

.reason-invalid_data {
    background: #f3f4f6;
    color: #4b5563;
}

.score-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    margin: 0.5rem 0;
    padding: 0.5rem;
    background: var(--bg-light);
    border-radius: 4px;
    font-size: 0.875rem;
}

.score-preview .total {
    margin-left: auto;
    color: var(--primary-color);
}

.unmatched-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    min-width: 280px;
}

.participant-picker {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 4px;
    font-size: 0.875rem;
}

@media (max-width: 768px) {
    .unmatched-card {
        grid-template-columns: 1fr;
    }
    .unmatched-actions {
        min-width: 0;
    }
}
</style>
@endpush

@push('scripts')
<script>
async function resolveRow(id) {
    const select = document.querySelector(`.participant-picker[data-id="${id}"]`);
    const participantId = select.value;

    if (!participantId) {
        alert('Sila pilih peserta dahulu');
        return;
    }

    if (!confirm('Padankan skor ini kepada peserta yang dipilih?')) return;

    try {
        const response = await fetch(`/admin/scores/unmatched/${id}/resolve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ participant_id: participantId })
        });

        const result = await response.json();

        if (result.success) {
            const card = document.getElementById(`row-${id}`);
            card.style.transition = 'opacity 0.3s';
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
            showToast(result.message);
        } else {
            alert(result.message || 'Ralat berlaku');
        }
    } catch (error) {
        alert('Ralat rangkaian: ' + error.message);
    }
}

async function discardRow(id) {
    if (!confirm('Buang rekod ini? Tidak boleh dipulihkan.')) return;

    try {
        const response = await fetch(`/admin/scores/unmatched/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            const card = document.getElementById(`row-${id}`);
            card.style.transition = 'opacity 0.3s';
            card.style.opacity = '0';
            setTimeout(() => card.remove(), 300);
            showToast(result.message);
        } else {
            alert(result.message || 'Ralat berlaku');
        }
    } catch (error) {
        alert('Ralat rangkaian: ' + error.message);
    }
}

function showToast(message) {
    const toast = document.getElementById('toast');
    if (toast) {
        toast.textContent = message;
        toast.className = 'toast show success';
        setTimeout(() => { toast.className = 'toast'; }, 3000);
    }
}

window.resolveRow = resolveRow;
window.discardRow = discardRow;
</script>
@endpush
