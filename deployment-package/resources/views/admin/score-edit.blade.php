@extends('layouts.app')

@section('title', 'Kemaskini Skor - ' . $participant->name)

@section('content')
<div class="section active">
    <h2 class="section-title">Kemaskini Skor</h2>

    <div class="participant-info" style="margin-bottom: 2rem;">
        <p><strong>ID:</strong> {{ $participant->id }}</p>
        <p><strong>Nama:</strong> {{ $participant->name }} {{ $participant->event_type === 'berkumpulan' ? '<span class="event-badge">Berkumpulan</span>' : '' }}</p>
        @if($participant->event_type !== 'berkumpulan')
            <p><strong>No. KP:</strong> {{ $participant->ic }}</p>
        @endif
        <p><strong>Pasukan:</strong> {{ $participant->team }}</p>
        <p><strong>Acara:</strong> {{ $participant->event_type }} ({{ $participant->gender }})</p>
        @if($participant->event_type === 'berkumpulan' && $participant->teamMembers && $participant->teamMembers->count() > 0)
            <div class="team-members-list">
                <strong>Ahli Pasukan:</strong>
                <ul class="team-members">
                    @foreach($participant->teamMembers->sortBy('member_order') as $member)
                        <li>
                            <span class="member-order">{{ $member->member_order }}</span>
                            <span class="member-name">{{ $member->name }}</span>
                            <span class="member-ic">({{ $member->ic }})</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>

    <form id="scoreEditForm" action="{{ route('admin.score.update', $participant->id) }}" method="POST">
        @csrf
        <div class="scores-grid">
            <div class="form-group">
                <label for="editG1">Game 1</label>
                <input type="number" id="editG1" name="g1" min="0" max="300" required value="{{ $participant->score->g1 ?? 0 }}">
            </div>
            <div class="form-group">
                <label for="editG2">Game 2</label>
                <input type="number" id="editG2" name="g2" min="0" max="300" required value="{{ $participant->score->g2 ?? 0 }}">
            </div>
            <div class="form-group">
                <label for="editG3">Game 3</label>
                <input type="number" id="editG3" name="g3" min="0" max="300" required value="{{ $participant->score->g3 ?? 0 }}">
            </div>
            <div class="form-group">
                <label for="editG4">Game 4</label>
                <input type="number" id="editG4" name="g4" min="0" max="300" required value="{{ $participant->score->g4 ?? 0 }}">
            </div>
            <div class="form-group">
                <label for="editG5">Game 5</label>
                <input type="number" id="editG5" name="g5" min="0" max="300" required value="{{ $participant->score->g5 ?? 0 }}">
            </div>
        </div>
        <div class="score-summary">
            <div class="summary-item">
                <span>Jumlah:</span>
                <strong id="editTotalScore">{{ $participant->score->total ?? 0 }}</strong>
            </div>
            <div class="summary-item">
                <span>Purata:</span>
                <strong id="editAvgScore">{{ $participant->score->average ?? 0 }}</strong>
            </div>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
            <a href="{{ route('admin') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const editInputs = ['editG1', 'editG2', 'editG3', 'editG4', 'editG5'];

editInputs.forEach(id => {
    document.getElementById(id).addEventListener('input', updateEditScoreSummary);
});

function updateEditScoreSummary() {
    const scores = editInputs.map(id => parseInt(document.getElementById(id).value) || 0);
    const total = scores.reduce((a, b) => a + b, 0);
    const avg = (total / 5).toFixed(1);

    document.getElementById('editTotalScore').textContent = total;
    document.getElementById('editAvgScore').textContent = avg;
}
</script>
@endpush
