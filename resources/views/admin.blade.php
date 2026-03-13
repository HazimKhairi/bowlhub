@extends('layouts.app')

@section('title', 'Panel Admin - Ukhuwah Strike Challenge')

@section('content')
<!-- Admin Section -->
<section id="admin" class="section active">
    <h2 class="section-title">Panel Admin</h2>
    <p style="text-align: center; color: var(--danger-color); font-weight: 600; margin-bottom: 1.5rem;">
        <i class="fas fa-lock"></i> Hanya Admin Boleh Menyerahkan Skor
    </p>

    <div class="admin-tabs">
        <button class="admin-tab-btn active" data-tab="scores">Urus Skor</button>
        <button class="admin-tab-btn" data-tab="participants">Senarai Peserta</button>
    </div>

    <!-- Score Management Tab -->
    <div class="admin-tab-content active" id="adminScoresTab">
        <div class="admin-header">
            <h3>Urus Skor Peserta</h3>
            <div class="admin-filters">
                <select id="adminEventTypeFilter">
                    <option value="">Semua Acara</option>
                    <option value="individu">Individu</option>
                    <option value="beregu">Beregu</option>
                    <option value="trio">Trio</option>
                    <option value="berkumpulan">Berkumpulan</option>
                </select>
                <select id="adminGenderFilter">
                    <option value="">Semua Jantina</option>
                    <option value="lelaki">Lelaki</option>
                    <option value="wanita">Wanita</option>
                </select>
            </div>
        </div>

        <div class="participants-list" id="adminParticipantsList">
            <!-- Participants will be loaded here -->
        </div>
    </div>

    <!-- Participants List Tab -->
    <div class="admin-tab-content" id="adminParticipantsTab">
        <div class="admin-header">
            <h3>Senarai Semua Peserta</h3>
            <button class="btn btn-sm btn-secondary" id="exportData">Eksport Data</button>
        </div>
        <div class="data-table-wrapper">
            <table class="data-table" id="allParticipantsTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>No. KP</th>
                        <th>Pasukan</th>
                        <th>Jantina</th>
                        <th>Acara</th>
                        <th>Jumlah Skor</th>
                        <th>Tindakan</th>
                    </tr>
                </thead>
                <tbody id="allParticipantsBody">
                    <!-- Participants data -->
                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- Score Edit Modal -->
<div class="modal" id="scoreModal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Daftar/Kemaskini Skor</h3>
            <button class="modal-close" id="closeModal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="participant-info" id="modalParticipantInfo"></div>
            <form id="scoreEditForm">
                <input type="hidden" id="editParticipantId">
                @csrf
                <div class="scores-grid">
                    <div class="form-group">
                        <label for="editG1">Game 1</label>
                        <input type="number" id="editG1" name="g1" min="0" max="300" required>
                    </div>
                    <div class="form-group">
                        <label for="editG2">Game 2</label>
                        <input type="number" id="editG2" name="g2" min="0" max="300" required>
                    </div>
                    <div class="form-group">
                        <label for="editG3">Game 3</label>
                        <input type="number" id="editG3" name="g3" min="0" max="300" required>
                    </div>
                    <div class="form-group">
                        <label for="editG4">Game 4</label>
                        <input type="number" id="editG4" name="g4" min="0" max="300" required>
                    </div>
                    <div class="form-group">
                        <label for="editG5">Game 5</label>
                        <input type="number" id="editG5" name="g5" min="0" max="300" required>
                    </div>
                </div>
                <div class="score-summary">
                    <div class="summary-item">
                        <span>Jumlah:</span>
                        <strong id="editTotalScore">0</strong>
                    </div>
                    <div class="summary-item">
                        <span>Purata:</span>
                        <strong id="editAvgScore">0</strong>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Simpan</button>
                    <button type="button" class="btn btn-danger" id="deleteParticipant">Padam</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Receipt Preview Modal -->
<div class="modal" id="receiptModal">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>Resit Pembayaran</h3>
            <button class="modal-close" id="closeReceiptModal">&times;</button>
        </div>
        <div class="modal-body">
            <div id="receiptPreview">
                <!-- Image preview for JPG/PNG -->
                <img id="receiptImage" src="" alt="Resit Pembayaran" style="display: none;">
                <!-- PDF preview -->
                <iframe id="receiptPdf" src="" style="display: none; width: 100%; height: 600px; border: none;"></iframe>
            </div>
        </div>
        <div class="modal-footer">
            <a id="receiptDownload" href="#" target="_blank" class="btn btn-secondary" download>
                <i class="fas fa-download"></i> Muat Turun
            </a>
            <button type="button" class="btn btn-secondary" id="closeReceiptModalBtn">Tutup</button>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending {
    background-color: #ffc107;
    color: #000;
}

.status-approved {
    background-color: #28a745;
    color: #fff;
}

.modal-large {
    max-width: 800px;
}

#receiptPreview {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 400px;
}

#receiptImage {
    max-width: 100%;
    max-height: 600px;
    object-fit: contain;
}
</style>
@endpush

@push('scripts')
<script>
// ========================================
// ADMIN PANEL FUNCTIONALITY
// ========================================

const API_ENDPOINTS = {
    participants: '{{ route('admin.participants') }}',
    updateScore: (id) => `/admin/score/${id}`,
    deleteParticipant: (id) => `/admin/participant/${id}`, // Will be added to routes later
    approveParticipant: (id) => `/admin/participant/${id}/approve` // Will be added to routes later
};

// Initialize admin panel
document.addEventListener('DOMContentLoaded', function() {
    initAdminPanel();
    loadParticipants();
});

function initAdminPanel() {
    // Admin tabs
    const adminTabs = document.querySelectorAll('.admin-tab-btn');
    const adminContents = document.querySelectorAll('.admin-tab-content');

    adminTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetTab = tab.dataset.tab;

            adminTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            adminContents.forEach(content => {
                content.classList.remove('active');
                if (content.id === `admin${targetTab.charAt(0).toUpperCase() + targetTab.slice(1)}Tab`) {
                    content.classList.add('active');
                }
            });
        });
    });

    // Filters
    const eventTypeFilter = document.getElementById('adminEventTypeFilter');
    const genderFilter = document.getElementById('adminGenderFilter');

    eventTypeFilter.addEventListener('change', loadParticipants);
    genderFilter.addEventListener('change', loadParticipants);

    // Modal
    const modal = document.getElementById('scoreModal');
    const closeModal = document.getElementById('closeModal');

    closeModal.addEventListener('click', () => {
        modal.classList.remove('active');
    });

    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

    // Score edit form
    const scoreEditForm = document.getElementById('scoreEditForm');
    const editInputs = ['editG1', 'editG2', 'editG3', 'editG4', 'editG5'];

    editInputs.forEach(id => {
        document.getElementById(id).addEventListener('input', updateEditScoreSummary);
    });

    scoreEditForm.addEventListener('submit', (e) => {
        e.preventDefault();
        saveScore();
    });

    // Delete participant
    document.getElementById('deleteParticipant').addEventListener('click', () => {
        const participantId = document.getElementById('editParticipantId').value;
        if (confirm('Adakah anda pasti mahu memadam peserta ini?')) {
            deleteParticipant(participantId);
        }
    });

    // Export data
    document.getElementById('exportData').addEventListener('click', exportData);

    // Receipt modal
    const receiptModal = document.getElementById('receiptModal');
    const closeReceiptModal = document.getElementById('closeReceiptModal');
    const closeReceiptModalBtn = document.getElementById('closeReceiptModalBtn');

    closeReceiptModal.addEventListener('click', () => {
        receiptModal.classList.remove('active');
    });

    closeReceiptModalBtn.addEventListener('click', () => {
        receiptModal.classList.remove('active');
    });

    receiptModal.addEventListener('click', (e) => {
        if (e.target === receiptModal) {
            receiptModal.classList.remove('active');
        }
    });
}

function updateEditScoreSummary() {
    const editInputs = ['editG1', 'editG2', 'editG3', 'editG4', 'editG5'];
    const scores = editInputs.map(id => parseInt(document.getElementById(id).value) || 0);
    const total = scores.reduce((a, b) => a + b, 0);
    const avg = (total / 5).toFixed(1);

    document.getElementById('editTotalScore').textContent = total;
    document.getElementById('editAvgScore').textContent = avg;
}

async function loadParticipants() {
    try {
        const eventType = document.getElementById('adminEventTypeFilter').value;
        const gender = document.getElementById('adminGenderFilter').value;

        const params = new URLSearchParams();
        if (eventType) params.append('event_type', eventType);
        if (gender) params.append('gender', gender);

        const response = await fetch(`${API_ENDPOINTS.participants}?${params.toString()}`);
        const participants = await response.json();

        renderAdminParticipantsList(participants);
        renderAllParticipantsTable(participants);
    } catch (error) {
        console.error('Error loading participants:', error);
        showToast('Ralat memuatkan peserta', 'error');
    }
}

function renderAdminParticipantsList(participants) {
    const container = document.getElementById('adminParticipantsList');

    if (participants.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada peserta dijumpai</p></div>';
        return;
    }

    container.innerHTML = participants.map(p => {
        const participantHasScores = p.score && (
            p.score.g1 > 0 ||
            p.score.g2 > 0 ||
            p.score.g3 > 0 ||
            p.score.g4 > 0 ||
            p.score.g5 > 0
        );

        const scoreStatusClass = participantHasScores ? 'score-badge' : 'score-badge score-empty';
        const scoreStatusText = participantHasScores ? `${p.score.total}` : 'Belum Ada Skor';

        // Status badge
        const statusBadge = p.status === 'approved'
            ? '<span class="status-badge status-approved">Diluluskan</span>'
            : '<span class="status-badge status-pending">Menunggu</span>';

        // Check if team event (beregu, trio, or berkumpulan) and build team members display
        const teamEventTypes = ['beregu', 'trio', 'berkumpulan'];
        const isTeamEvent = teamEventTypes.includes(p.event_type);
        let teamMembersHtml = '';

        if (isTeamEvent && p.team_members && p.team_members.length > 0) {
            teamMembersHtml = `
                <div class="team-members-list">
                    <strong>Ahli Pasukan:</strong>
                    <ul class="team-members">
                        ${p.team_members.map(member => `
                            <li>
                                <span class="member-order">${member.member_order}</span>
                                <span class="member-name">${member.name}</span>
                                <span class="member-ic">(${member.ic})</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>
            `;
        }

        // Generate event badge for team events
        const eventBadge = isTeamEvent ? `<span class="event-badge">${p.event_type.charAt(0).toUpperCase() + p.event_type.slice(1)}</span>` : '';

        // Action buttons based on status
        const actionButtons = p.status === 'pending' ? `
            <button class="btn btn-sm btn-success" onclick="viewReceipt('${p.payment_receipt || ''}')"><i class="fas fa-receipt"></i> Lihat Resit</button>
            <button class="btn btn-sm btn-primary" onclick="approveParticipant('${p.id}')"><i class="fas fa-check"></i> Lulus</button>
            <button class="btn btn-sm btn-danger" onclick="deleteParticipant('${p.id}')"><i class="fas fa-trash"></i> Padam</button>
        ` : `
            <button class="btn btn-sm btn-success" onclick="viewReceipt('${p.payment_receipt || ''}')"><i class="fas fa-receipt"></i> Lihat Resit</button>
            <button class="btn btn-sm ${participantHasScores ? 'btn-secondary' : 'btn-primary'}" onclick="openScoreModal('${p.id}', ${JSON.stringify(p).replace(/"/g, '&quot;')})">
                <i class="fas fa-${participantHasScores ? 'edit' : 'plus'}"></i> ${participantHasScores ? 'Kemaskini' : 'Masukkan Skor'}
            </button>
            <button class="btn btn-sm btn-danger" ${participantHasScores ? 'disabled' : ''} onclick="deleteParticipant('${p.id}')"><i class="fas fa-trash"></i> Padam</button>
        `;

        return `
        <div class="participant-card ${isTeamEvent ? 'team-card' : ''}">
            <div class="participant-info">
                <h4>${p.name} ${eventBadge}</h4>
                <p><strong>ID:</strong> ${p.id} ${isTeamEvent ? '' : `| <strong>IC:</strong> ${p.ic}`}</p>
                <p><strong>Status:</strong> ${statusBadge}</p>
                <p><strong>Pasukan:</strong> ${p.team} | <strong>Acara:</strong> ${p.event_type} (${p.gender})</p>
                ${teamMembersHtml}
            </div>
            <div class="participant-scores">
                <div class="scores-container">
                    <!-- Row 1: G1, G2, G3 -->
                    <div class="scores-row">
                        <div class="score-item">
                            <span class="score-label">G1</span>
                            <span class="score-value">${p.score ? p.score.g1 : 0}</span>
                        </div>
                        <div class="score-item">
                            <span class="score-label">G2</span>
                            <span class="score-value">${p.score ? p.score.g2 : 0}</span>
                        </div>
                        <div class="score-item">
                            <span class="score-label">G3</span>
                            <span class="score-value">${p.score ? p.score.g3 : 0}</span>
                        </div>
                    </div>

                    <!-- Row 2: G4, G5 -->
                    <div class="scores-row scores-row-secondary">
                        <div class="score-item">
                            <span class="score-label">G4</span>
                            <span class="score-value">${p.score ? p.score.g4 : 0}</span>
                        </div>
                        <div class="score-item">
                            <span class="score-label">G5</span>
                            <span class="score-value">${p.score ? p.score.g5 : 0}</span>
                        </div>
                    </div>
                </div>

                <!-- Total Score Section -->
                <div class="score-total ${!participantHasScores ? 'score-total-empty' : ''}">
                    <div class="total-label">Jumlah</div>
                    <div class="total-value">${scoreStatusText}</div>
                    ${participantHasScores ? `<div class="total-avg">Purata: ${((p.score.total) / 5).toFixed(1)}</div>` : ''}
                </div>
            </div>
            <div class="participant-actions">
                ${actionButtons}
            </div>
        </div>
    `;
    }).join('');
}

function renderAllParticipantsTable(participants) {
    const tbody = document.getElementById('allParticipantsBody');

    if (participants.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = participants.map(p => {
        const participantHasScores = p.score && (
            p.score.g1 > 0 ||
            p.score.g2 > 0 ||
            p.score.g3 > 0 ||
            p.score.g4 > 0 ||
            p.score.g5 > 0
        );

        const scoreDisplay = participantHasScores ? `<strong>${p.score.total}</strong>` : '<span class="text-muted">-</span>';

        // For team events (beregu, trio, berkumpulan), show team name instead of captain's name
        const teamEventTypes = ['beregu', 'trio', 'berkumpulan'];
        const isTeamEvent = teamEventTypes.includes(p.event_type);
        const eventBadge = isTeamEvent ? `<span class="event-badge-small">${p.event_type.charAt(0).toUpperCase() + p.event_type.slice(1)}</span>` : '';
        const displayName = isTeamEvent ? `<strong>${p.name}</strong> ${eventBadge}` : p.name;
        const displayIc = isTeamEvent ? `<span class="text-muted">-</span>` : p.ic;

        return `
        <tr>
            <td>${p.id}</td>
            <td>${displayName}</td>
            <td>${displayIc}</td>
            <td>${p.team}</td>
            <td>${p.gender}</td>
            <td>${p.event_type}</td>
            <td>${scoreDisplay}</td>
            <td>
                <button class="btn btn-sm ${participantHasScores ? 'btn-secondary' : 'btn-primary'}" onclick="openScoreModal('${p.id}', ${JSON.stringify(p).replace(/"/g, '&quot;')})">
                    <i class="fas fa-${participantHasScores ? 'edit' : 'plus'}"></i>
                </button>
            </td>
        </tr>
    `;
    }).join('');
}

function openScoreModal(participantId, participant) {
    const modal = document.getElementById('scoreModal');

    // Check if team event (beregu, trio, or berkumpulan) and build team members display
    const teamEventTypes = ['beregu', 'trio', 'berkumpulan'];
    const isTeamEvent = teamEventTypes.includes(participant.event_type);
    let teamMembersHtml = '';

    if (isTeamEvent && participant.team_members && participant.team_members.length > 0) {
        teamMembersHtml = `
            <div class="team-members-list">
                <strong>Ahli Pasukan:</strong>
                <ul class="team-members">
                    ${participant.team_members.map(member => `
                        <li>
                            <span class="member-order">${member.member_order}</span>
                            <span class="member-name">${member.name}</span>
                            <span class="member-ic">(${member.ic})</span>
                        </li>
                    `).join('')}
                </ul>
            </div>
        `;
    }

    // Generate event badge for team events
    const eventBadge = isTeamEvent ? `<span class="event-badge">${participant.event_type.charAt(0).toUpperCase() + participant.event_type.slice(1)}</span>` : '';

    // Show participant info
    document.getElementById('modalParticipantInfo').innerHTML = `
        <p><strong>ID:</strong> ${participant.id}</p>
        <p><strong>Nama:</strong> ${participant.name} ${eventBadge}</p>
        ${!isTeamEvent ? `<p><strong>No. KP:</strong> ${participant.ic}</p>` : ''}
        <p><strong>Pasukan:</strong> ${participant.team}</p>
        <p><strong>Acara:</strong> ${participant.event_type} (${participant.gender})</p>
        ${teamMembersHtml}
    `;

    // Set current scores
    document.getElementById('editParticipantId').value = participant.id;
    document.getElementById('editG1').value = participant.score ? participant.score.g1 : 0;
    document.getElementById('editG2').value = participant.score ? participant.score.g2 : 0;
    document.getElementById('editG3').value = participant.score ? participant.score.g3 : 0;
    document.getElementById('editG4').value = participant.score ? participant.score.g4 : 0;
    document.getElementById('editG5').value = participant.score ? participant.score.g5 : 0;

    // Update summary
    const total = participant.score ? participant.score.total : 0;
    const avg = participant.score ? participant.score.average : 0;
    document.getElementById('editTotalScore').textContent = total;
    document.getElementById('editAvgScore').textContent = avg;

    modal.classList.add('active');
}

async function saveScore() {
    try {
        const participantId = document.getElementById('editParticipantId').value;
        const formData = new FormData(document.getElementById('scoreEditForm'));

        const response = await fetch(API_ENDPOINTS.updateScore(participantId), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                g1: parseInt(formData.get('g1')) || 0,
                g2: parseInt(formData.get('g2')) || 0,
                g3: parseInt(formData.get('g3')) || 0,
                g4: parseInt(formData.get('g4')) || 0,
                g5: parseInt(formData.get('g5')) || 0
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            document.getElementById('scoreModal').classList.remove('active');
            loadParticipants();
        } else {
            showToast('Ralat menyimpan skor', 'error');
        }
    } catch (error) {
        console.error('Error saving score:', error);
        showToast('Ralat menyimpan skor', 'error');
    }
}

async function deleteParticipant(participantId) {
    try {
        const response = await fetch(API_ENDPOINTS.deleteParticipant(participantId), {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            document.getElementById('scoreModal').classList.remove('active');
            loadParticipants();
        } else {
            showToast('Ralat memadam peserta', 'error');
        }
    } catch (error) {
        console.error('Error deleting participant:', error);
        showToast('Ralat memadam peserta', 'error');
    }
}

async function approveParticipant(participantId) {
    try {
        const response = await fetch(`/admin/participant/${participantId}/approve`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const result = await response.json();

        if (result.success) {
            showToast(result.message, 'success');
            loadParticipants();
        } else {
            showToast(result.message, 'error');
        }
    } catch (error) {
        console.error('Error approving participant:', error);
        showToast('Ralat meluluskan peserta', 'error');
    }
}

async function exportData() {
    try {
        const response = await fetch(API_ENDPOINTS.participants);
        const participants = await response.json();

        const dataStr = JSON.stringify(participants, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `bowling_participants_${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
        showToast('Data berjaya dieksport!');
    } catch (error) {
        console.error('Error exporting data:', error);
        showToast('Ralat mengeksport data', 'error');
    }
}

function viewReceipt(receiptPath) {
    console.log('viewReceipt called with path:', receiptPath);

    if (receiptPath) {
        const modal = document.getElementById('receiptModal');
        const img = document.getElementById('receiptImage');
        const pdf = document.getElementById('receiptPdf');
        const download = document.getElementById('receiptDownload');
        const fileUrl = '/' + receiptPath;

        console.log('File URL:', fileUrl);

        // Check file extension
        const isPdf = receiptPath.toLowerCase().endsWith('.pdf');

        if (isPdf) {
            // Show PDF in iframe
            img.style.display = 'none';
            pdf.style.display = 'block';
            pdf.src = fileUrl;
        } else {
            // Show image
            pdf.style.display = 'none';
            img.style.display = 'block';
            img.src = fileUrl;
        }

        // Set download link
        download.href = fileUrl;

        console.log('Adding active class to modal');
        modal.classList.add('active');
    } else {
        console.log('No receipt path provided');
        showToast('Tiada resit dimuatnaik', 'error');
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}

// Make function global for onclick handlers
window.openScoreModal = openScoreModal;
window.viewReceipt = viewReceipt;
window.approveParticipant = approveParticipant;
</script>
@endpush
