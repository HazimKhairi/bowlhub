// ========================================
// DATA STORE (LocalStorage)
// ========================================

const STORAGE_KEYS = {
    PARTICIPANTS: 'bowling_participants',
    TEAMS: 'bowling_teams'
};

// Initialize data
function initializeData() {
    if (!localStorage.getItem(STORAGE_KEYS.PARTICIPANTS)) {
        // Sample data for demonstration
        const sampleParticipants = [
            {
                id: 'P001',
                name: 'Ahmad bin Ali',
                ic: '900101015555',
                phone: '0123456789',
                team: 'SMK Sultan Alam Shah',
                gender: 'lelaki',
                eventType: 'individu',
                scores: { g1: 180, g2: 195, g3: 210, g4: 188, g5: 200 },
                total: 973,
                average: 194.6,
                createdAt: new Date().toISOString()
            },
            {
                id: 'P002',
                name: 'Siti Aminah binti Hasan',
                ic: '920505085555',
                phone: '0134567890',
                team: 'SMK Convent Bukit Nanas',
                gender: 'wanita',
                eventType: 'individu',
                scores: { g1: 165, g2: 178, g3: 185, g4: 192, g5: 188 },
                total: 908,
                average: 181.6,
                createdAt: new Date().toISOString()
            },
            {
                id: 'P003',
                name: 'Mohd Razif bin Roslan',
                ic: '880810145555',
                phone: '0145678901',
                team: 'SMK Victoria Institution',
                gender: 'lelaki',
                eventType: 'individu',
                scores: { g1: 200, g2: 215, g3: 205, g4: 198, g5: 212 },
                total: 1030,
                average: 206,
                createdAt: new Date().toISOString()
            },
            {
                id: 'P004',
                name: 'Farah Lee',
                ic: '951215105555',
                phone: '0198765432',
                team: 'SMK St. Mary',
                gender: 'wanita',
                eventType: 'individu',
                scores: { g1: 175, g2: 182, g3: 178, g4: 188, g5: 185 },
                total: 908,
                average: 181.6,
                createdAt: new Date().toISOString()
            },
            {
                id: 'P005',
                name: 'Kumar a/l Rajan',
                ic: '910305025555',
                phone: '0172345678',
                team: 'SMK Methodist ACS',
                gender: 'lelaki',
                eventType: 'individu',
                scores: { g1: 168, g2: 175, g3: 182, g4: 178, g5: 185 },
                total: 888,
                average: 177.6,
                createdAt: new Date().toISOString()
            },
            // Beregu sample
            {
                id: 'P006',
                name: 'Zainal Abidin',
                ic: '891122025555',
                phone: '0183456789',
                team: 'PPD Petaling',
                gender: 'lelaki',
                eventType: 'beregu',
                teamMembers: [
                    { name: 'Zainal Abidin', ic: '891122025555' },
                    { name: 'Rizman Hizan', ic: '900303045555' }
                ],
                scores: { g1: 175, g2: 188, g3: 192, g4: 185, g5: 190 },
                total: 930,
                average: 186,
                createdAt: new Date().toISOString()
            },
            // Trio sample
            {
                id: 'P007',
                name: 'Nurul Izzah',
                ic: '940707045555',
                phone: '0164567890',
                team: 'SMK Raja Muda',
                gender: 'wanita',
                eventType: 'trio',
                teamMembers: [
                    { name: 'Nurul Izzah', ic: '940707045555' },
                    { name: 'Aisyah Humaira', ic: '950812065555' },
                    { name: 'Sarah Lee', ic: '960405085555' }
                ],
                scores: { g1: 168, g2: 175, g3: 180, g4: 178, g5: 182 },
                total: 883,
                average: 176.6,
                createdAt: new Date().toISOString()
            },
            // Berkumpulan sample
            {
                id: 'P008',
                name: 'Pasukan Boling KL',
                ic: 'TEAM001',
                phone: '0123456789',
                team: 'PPD Kuala Lumpur',
                gender: 'lelaki',
                eventType: 'berkumpulan',
                scores: { g1: 890, g2: 920, g3: 945, g4: 910, g5: 935 },
                total: 4610,
                average: 922,
                createdAt: new Date().toISOString()
            }
        ];
        localStorage.setItem(STORAGE_KEYS.PARTICIPANTS, JSON.stringify(sampleParticipants));
    }
}

// Data CRUD operations
function getParticipants() {
    const data = localStorage.getItem(STORAGE_KEYS.PARTICIPANTS);
    return data ? JSON.parse(data) : [];
}

function saveParticipant(participant) {
    const participants = getParticipants();
    participants.push(participant);
    localStorage.setItem(STORAGE_KEYS.PARTICIPANTS, JSON.stringify(participants));
}

function updateParticipant(id, updates) {
    const participants = getParticipants();
    const index = participants.findIndex(p => p.id === id);
    if (index !== -1) {
        participants[index] = { ...participants[index], ...updates };
        localStorage.setItem(STORAGE_KEYS.PARTICIPANTS, JSON.stringify(participants));
        return participants[index];
    }
    return null;
}

function deleteParticipant(id) {
    const participants = getParticipants();
    const filtered = participants.filter(p => p.id !== id);
    localStorage.setItem(STORAGE_KEYS.PARTICIPANTS, JSON.stringify(filtered));
}

function getParticipantById(id) {
    const participants = getParticipants();
    return participants.find(p => p.id === id);
}

// Generate unique ID
function generateId() {
    const participants = getParticipants();
    const num = participants.length + 1;
    return 'P' + String(num).padStart(3, '0');
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

function calculateTotal(scores) {
    return scores.g1 + scores.g2 + scores.g3 + scores.g4 + scores.g5;
}

function calculateAverage(total) {
    return (total / 5).toFixed(1);
}

function getRankLabel(rank) {
    const labels = {
        1: 'JOHAN',
        2: 'NAIB JOHAN',
        3: 'KETIGA'
    };
    return labels[rank] || `KE-${rank}`;
}

function getRankClass(rank) {
    if (rank === 1) return 'rank-1';
    if (rank === 2) return 'rank-2';
    if (rank === 3) return 'rank-3';
    return 'rank-other';
}

function getMedalClass(rank) {
    if (rank === 1) return 'medal-gold';
    if (rank === 2) return 'medal-silver';
    if (rank === 3) return 'medal-bronze';
    return '';
}

function hasScores(participant) {
    return participant.scores && (
        participant.scores.g1 > 0 ||
        participant.scores.g2 > 0 ||
        participant.scores.g3 > 0 ||
        participant.scores.g4 > 0 ||
        participant.scores.g5 > 0
    );
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    toast.textContent = message;
    toast.className = `toast show ${type}`;
    setTimeout(() => {
        toast.className = 'toast';
    }, 3000);
}

// ========================================
// NAVIGATION
// ========================================
// Navigation is handled by Laravel routes - no JavaScript interception needed
// Links work normally with browser navigation

// ========================================
// LEADERBOARD
// ========================================

let currentEvent = 'individu';
let currentGender = 'lelaki';

function initLeaderboardTabs() {
    const eventTabs = document.querySelectorAll('.tab-btn');
    const genderTabs = document.querySelectorAll('.gender-tab-btn');

    // Check if leaderboard tabs exist on this page
    if (eventTabs.length === 0) {
        return; // Exit if we're not on the leaderboard page
    }

    eventTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            eventTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentEvent = tab.dataset.event;

            // Show/hide gender tabs
            const genderTabsContainer = document.getElementById('genderTabs');
            if (currentEvent === 'pingat') {
                genderTabsContainer.style.display = 'none';
            } else {
                genderTabsContainer.style.display = 'flex';
            }

            renderLeaderboard();
        });
    });

    genderTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            genderTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            currentGender = tab.dataset.gender;
            renderLeaderboard();
        });
    });
}

function renderLeaderboard() {
    const content = document.getElementById('leaderboardContent');

    // Check if leaderboard content element exists
    if (!content) {
        return; // Exit if we're not on the leaderboard page
    }

    if (currentEvent === 'pingat') {
        content.innerHTML = renderMedalStandings();
        return;
    }

    const participants = getParticipants();
    const filtered = participants.filter(p =>
        p.eventType === currentEvent &&
        p.gender === currentGender &&
        hasScores(p)
    );

    // Sort by total score descending
    const ranked = filtered
        .map(p => ({ ...p, rank: 0 }))
        .sort((a, b) => b.total - a.total)
        .map((p, index) => ({ ...p, rank: index + 1 }));

    switch (currentEvent) {
        case 'individu':
            content.innerHTML = renderIndividualLeaderboard(ranked);
            break;
        case 'beregu':
            content.innerHTML = renderBereguLeaderboard(ranked);
            break;
        case 'trio':
            content.innerHTML = renderTrioLeaderboard(ranked);
            break;
        case 'berkumpulan':
            content.innerHTML = renderBerkumpulanLeaderboard(ranked);
            break;
    }
}

function renderIndividualLeaderboard(participants) {
    // Section 1: Acara Individu
    let html = `
        <div class="leaderboard-section">
            <h3><i class="fas fa-user"></i> Acara Individu - ${currentGender === 'lelaki' ? 'Lelaki' : 'Wanita'}</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Lane</th>
                            <th>Nama Peserta</th>
                            <th>Daerah</th>
                            <th>G1</th>
                            <th>G2</th>
                            <th>G3</th>
                            <th>G4</th>
                            <th>G5</th>
                            <th>Jumlah</th>
                            <th>Avg</th>
                            <th>Diff</th>
                            <th>Ranking</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    if (participants.length === 0) {
        html += `<tr><td colspan="12"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
    } else {
        const maxScore = Math.max(...participants.map(p => Math.max(p.scores.g1, p.scores.g2, p.scores.g3, p.scores.g4, p.scores.g5)));

        participants.forEach((p, index) => {
            const diff = maxScore - Math.max(p.scores.g1, p.scores.g2, p.scores.g3, p.scores.g4, p.scores.g5);
            html += `
                <tr>
                    <td>${index + 1}</td>
                    <td>${p.name}</td>
                    <td>${p.team}</td>
                    <td>${p.scores.g1}</td>
                    <td>${p.scores.g2}</td>
                    <td>${p.scores.g3}</td>
                    <td>${p.scores.g4}</td>
                    <td>${p.scores.g5}</td>
                    <td><strong>${p.total}</strong></td>
                    <td>${p.average}</td>
                    <td>${diff}</td>
                    <td><span class="rank-badge ${getRankClass(p.rank)}">${getRankLabel(p.rank)}</span></td>
                </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    // Section 2: Pemenang Individu (Top 5)
    html += `
        <div class="leaderboard-section">
            <h3><i class="fas fa-trophy"></i> Pemenang Individu - ${currentGender === 'lelaki' ? 'Lelaki' : 'Wanita'}</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kedudukan</th>
                            <th>Nama Pemenang</th>
                            <th>Pasukan</th>
                            <th>Skor</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    const top5 = participants.slice(0, 5);
    if (top5.length === 0) {
        html += `<tr><td colspan="4"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
    } else {
        top5.forEach(p => {
            html += `
                <tr>
                    <td><span class="rank-badge ${getRankClass(p.rank)}">${getRankLabel(p.rank)}</span></td>
                    <td>${p.name}</td>
                    <td>${p.team}</td>
                    <td><strong>${p.total}</strong></td>
                </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    // Section 3: Jatuhan Pin Tertinggi (Highest Single Game)
    html += `
        <div class="leaderboard-section">
            <h3><i class="fas fa-star"></i> Jatuhan Pin Tertinggi - ${currentGender === 'lelaki' ? 'Lelaki' : 'Wanita'}</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Pasukan</th>
                            <th>Skor Tertinggi</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    // Get highest game scores
    const highGames = participants
        .map(p => ({
            name: p.name,
            team: p.team,
            highScore: Math.max(p.scores.g1, p.scores.g2, p.scores.g3, p.scores.g4, p.scores.g5)
        }))
        .sort((a, b) => b.highScore - a.highScore)
        .slice(0, 5);

    if (highGames.length === 0) {
        html += `<tr><td colspan="3"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
    } else {
        highGames.forEach(hg => {
            html += `
                <tr>
                    <td>${hg.name}</td>
                    <td>${hg.team}</td>
                    <td><strong>${hg.highScore}</strong></td>
                </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    return html;
}

function renderBereguLeaderboard(participants) {
    let html = `
        <div class="leaderboard-section">
            <h3><i class="fas fa-users"></i> Acara Beregu - ${currentGender === 'lelaki' ? 'Lelaki' : 'Wanita'}</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Peserta 1</th>
                            <th>Peserta 2</th>
                            <th>Daerah</th>
                            <th>G1</th>
                            <th>G2</th>
                            <th>G3</th>
                            <th>G4</th>
                            <th>G5</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    if (participants.length === 0) {
        html += `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
    } else {
        participants.forEach(p => {
            const member1 = p.teamMembers?.[0] || { name: '-' };
            const member2 = p.teamMembers?.[1] || { name: '-' };
            html += `
                <tr>
                    <td>${member1.name}</td>
                    <td>${member2.name}</td>
                    <td>${p.team}</td>
                    <td>${p.scores.g1}</td>
                    <td>${p.scores.g2}</td>
                    <td>${p.scores.g3}</td>
                    <td>${p.scores.g4}</td>
                    <td>${p.scores.g5}</td>
                    <td><strong>${p.total}</strong></td>
                </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    return html;
}

function renderTrioLeaderboard(participants) {
    let html = `
        <div class="leaderboard-section">
            <h3><i class="fas fa-user-friends"></i> Acara Trio - ${currentGender === 'lelaki' ? 'Lelaki' : 'Wanita'}</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Peserta 1</th>
                            <th>Peserta 2</th>
                            <th>Peserta 3</th>
                            <th>Daerah</th>
                            <th>G1</th>
                            <th>G2</th>
                            <th>G3</th>
                            <th>G4</th>
                            <th>G5</th>
                            <th>Jumlah</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    if (participants.length === 0) {
        html += `<tr><td colspan="10"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
    } else {
        participants.forEach(p => {
            const member1 = p.teamMembers?.[0] || { name: '-' };
            const member2 = p.teamMembers?.[1] || { name: '-' };
            const member3 = p.teamMembers?.[2] || { name: '-' };
            html += `
                <tr>
                    <td>${member1.name}</td>
                    <td>${member2.name}</td>
                    <td>${member3.name}</td>
                    <td>${p.team}</td>
                    <td>${p.scores.g1}</td>
                    <td>${p.scores.g2}</td>
                    <td>${p.scores.g3}</td>
                    <td>${p.scores.g4}</td>
                    <td>${p.scores.g5}</td>
                    <td><strong>${p.total}</strong></td>
                </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    return html;
}

function renderBerkumpulanLeaderboard(participants) {
    // Section 1: Acara Berkumpulan
    let html = `
        <div class="leaderboard-section">
            <h3><i class="fas fa-users"></i> Acara Berkumpulan - ${currentGender === 'lelaki' ? 'Lelaki' : 'Wanita'}</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Pasukan</th>
                            <th>G1</th>
                            <th>G2</th>
                            <th>G3</th>
                            <th>G4</th>
                            <th>G5</th>
                            <th>Jumlah</th>
                            <th>Avg5</th>
                            <th>Rank</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    if (participants.length === 0) {
        html += `<tr><td colspan="9"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
    } else {
        participants.forEach(p => {
            html += `
                <tr>
                    <td>${p.name}</td>
                    <td>${p.scores.g1}</td>
                    <td>${p.scores.g2}</td>
                    <td>${p.scores.g3}</td>
                    <td>${p.scores.g4}</td>
                    <td>${p.scores.g5}</td>
                    <td><strong>${p.total}</strong></td>
                    <td>${p.average}</td>
                    <td><span class="rank-badge ${getRankClass(p.rank)}">${getRankLabel(p.rank)}</span></td>
                </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    // Section 2: Pemenang Berkumpulan
    html += `
        <div class="leaderboard-section">
            <h3><i class="fas fa-trophy"></i> Pemenang Berkumpulan - ${currentGender === 'lelaki' ? 'Lelaki' : 'Wanita'}</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Kedudukan</th>
                            <th>Pasukan</th>
                            <th>Skor</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    const top5 = participants.slice(0, 5);
    if (top5.length === 0) {
        html += `<tr><td colspan="3"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
    } else {
        top5.forEach(p => {
            html += `
                <tr>
                    <td><span class="rank-badge ${getRankClass(p.rank)}">${getRankLabel(p.rank)}</span></td>
                    <td>${p.name}</td>
                    <td><strong>${p.total}</strong></td>
                </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    return html;
}

function renderMedalStandings() {
    const participants = getParticipants();

    // Calculate medals for each team
    const medalCounts = {};

    // Process each event type and gender
    const eventTypes = ['individu', 'beregu', 'trio', 'berkumpulan'];
    const genders = ['lelaki', 'wanita'];

    eventTypes.forEach(eventType => {
        genders.forEach(gender => {
            const filtered = participants.filter(p =>
                p.eventType === eventType &&
                p.gender === gender &&
                hasScores(p)
            ).sort((a, b) => b.total - a.total);

            // Award medals to top 5
            filtered.slice(0, 5).forEach((p, index) => {
                const team = p.team;
                if (!medalCounts[team]) {
                    medalCounts[team] = { emas: 0, perak: 0, gangsa: 0, fourth: 0, fifth: 0 };
                }

                if (index === 0) medalCounts[team].emas++;
                else if (index === 1) medalCounts[team].perak++;
                else if (index === 2) medalCounts[team].gangsa++;
                else if (index === 3) medalCounts[team].fourth++;
                else if (index === 4) medalCounts[team].fifth++;
            });
        });
    });

    // Convert to array and sort by gold, then silver, then bronze
    const standings = Object.entries(medalCounts)
        .map(([team, medals]) => ({ team, ...medals }))
        .sort((a, b) => {
            if (b.emas !== a.emas) return b.emas - a.emas;
            if (b.perak !== a.perak) return b.perak - a.perak;
            return b.gangsa - a.gangsa;
        })
        .map((s, index) => ({ ...s, rank: index + 1 }));

    let html = `
        <div class="leaderboard-section">
            <h3><i class="fas fa-medal"></i> Kedudukan Pingat Keseluruhan</h3>
            <div class="data-table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bil</th>
                            <th>Pasukan</th>
                            <th><i class="fas fa-circle" style="color: #fbbf24;"></i> Emas</th>
                            <th><i class="fas fa-circle" style="color: #9ca3af;"></i> Perak</th>
                            <th><i class="fas fa-circle" style="color: #cd7f32;"></i> Gangsa</th>
                            <th>Ke-4</th>
                            <th>Ke-5</th>
                        </tr>
                    </thead>
                    <tbody>
    `;

    if (standings.length === 0) {
        html += `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
    } else {
        standings.forEach(s => {
            html += `
                <tr>
                    <td>${s.rank}</td>
                    <td><strong>${s.team}</strong></td>
                    <td><span class="medal-badge medal-gold">${s.emas}</span></td>
                    <td><span class="medal-badge medal-silver">${s.perak}</span></td>
                    <td><span class="medal-badge medal-bronze">${s.gangsa}</span></td>
                    <td>${s.fourth}</td>
                    <td>${s.fifth}</td>
                </tr>
            `;
        });
    }

    html += `
                    </tbody>
                </table>
            </div>
        </div>
    `;

    return html;
}

// ========================================
// REGISTRATION FORM
// ========================================
// Registration form handling is now managed by Laravel backend
// See registration.blade.php for form validation and submission logic

// ========================================
// ADMIN PANEL
// ========================================

function initAdminPanel() {
    // Admin tabs
    const adminTabs = document.querySelectorAll('.admin-tab-btn');
    const adminContents = document.querySelectorAll('.admin-tab-content');

    // Check if admin panel elements exist on this page
    if (adminTabs.length === 0 || adminContents.length === 0) {
        return; // Exit if we're not on the admin page
    }

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

    eventTypeFilter.addEventListener('change', renderAdminParticipantsList);
    genderFilter.addEventListener('change', renderAdminParticipantsList);

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

    function updateEditScoreSummary() {
        const scores = editInputs.map(id => parseInt(document.getElementById(id).value) || 0);
        const total = scores.reduce((a, b) => a + b, 0);
        const avg = (total / 5).toFixed(1);

        document.getElementById('editTotalScore').textContent = total;
        document.getElementById('editAvgScore').textContent = avg;
    }

    scoreEditForm.addEventListener('submit', (e) => {
        e.preventDefault();

        const participantId = document.getElementById('editParticipantId').value;
        const scores = {
            g1: parseInt(document.getElementById('editG1').value) || 0,
            g2: parseInt(document.getElementById('editG2').value) || 0,
            g3: parseInt(document.getElementById('editG3').value) || 0,
            g4: parseInt(document.getElementById('editG4').value) || 0,
            g5: parseInt(document.getElementById('editG5').value) || 0
        };

        const total = calculateTotal(scores);
        const average = calculateAverage(total);

        updateParticipant(participantId, {
            scores: scores,
            total: total,
            average: parseFloat(average)
        });

        showToast('Skor berjaya disimpan!');
        modal.classList.remove('active');
        renderAdminParticipantsList();
        renderAllParticipantsTable();
    });

    // Delete participant
    document.getElementById('deleteParticipant').addEventListener('click', () => {
        const participantId = document.getElementById('editParticipantId').value;
        if (confirm('Adakah anda pasti mahu memadam peserta ini?')) {
            deleteParticipant(participantId);
            showToast('Peserta berjaya dipadam.', 'error');
            modal.classList.remove('active');
            renderAdminParticipantsList();
            renderAllParticipantsTable();
        }
    });

    // Export data
    document.getElementById('exportData').addEventListener('click', () => {
        const participants = getParticipants();
        const dataStr = JSON.stringify(participants, null, 2);
        const blob = new Blob([dataStr], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `bowling_participants_${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
        showToast('Data berjaya dieksport!');
    });
}

function renderAdminParticipantsList() {
    const container = document.getElementById('adminParticipantsList');
    const eventTypeFilter = document.getElementById('adminEventTypeFilter').value;
    const genderFilter = document.getElementById('adminGenderFilter').value;

    let participants = getParticipants();

    if (eventTypeFilter) {
        participants = participants.filter(p => p.eventType === eventTypeFilter);
    }

    if (genderFilter) {
        participants = participants.filter(p => p.gender === genderFilter);
    }

    if (participants.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada peserta dijumpai</p></div>';
        return;
    }

    container.innerHTML = participants.map(p => {
        const participantHasScores = hasScores(p);
        const scoreStatusClass = participantHasScores ? 'score-badge' : 'score-badge score-empty';
        const scoreStatusText = participantHasScores ? `${p.total}` : 'Belum Ada Skor';

        return `
        <div class="participant-card">
            <div class="participant-info">
                <h4>${p.name}</h4>
                <p><strong>ID:</strong> ${p.id} | <strong>IC:</strong> ${p.ic}</p>
                <p><strong>Pasukan:</strong> ${p.team} | <strong>Acara:</strong> ${p.eventType} (${p.gender})</p>
            </div>
            <div class="participant-scores">
                <div class="scores">
                    <span class="score-badge">G1: ${p.scores.g1}</span>
                    <span class="score-badge">G2: ${p.scores.g2}</span>
                    <span class="score-badge">G3: ${p.scores.g3}</span>
                    <span class="score-badge">G4: ${p.scores.g4}</span>
                    <span class="score-badge">G5: ${p.scores.g5}</span>
                </div>
                <div class="total ${!participantHasScores ? 'text-muted' : ''}">Jumlah: ${scoreStatusText}</div>
            </div>
            <div class="participant-actions">
                <button class="btn btn-sm ${participantHasScores ? 'btn-secondary' : 'btn-primary'}" onclick="openScoreModal('${p.id}')">
                    <i class="fas fa-${participantHasScores ? 'edit' : 'plus'}"></i> ${participantHasScores ? 'Kemaskini' : 'Masukkan Skor'}
                </button>
            </div>
        </div>
    `;
    }).join('');
}

function renderAllParticipantsTable() {
    const tbody = document.getElementById('allParticipantsBody');
    const participants = getParticipants();

    if (participants.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>';
        return;
    }

    tbody.innerHTML = participants.map(p => {
        const participantHasScores = hasScores(p);
        const scoreDisplay = participantHasScores ? `<strong>${p.total}</strong>` : '<span class="text-muted">-</span>';

        return `
        <tr>
            <td>${p.id}</td>
            <td>${p.name}</td>
            <td>${p.ic}</td>
            <td>${p.team}</td>
            <td>${p.gender}</td>
            <td>${p.eventType}</td>
            <td>${scoreDisplay}</td>
            <td>
                <button class="btn btn-sm ${participantHasScores ? 'btn-secondary' : 'btn-primary'}" onclick="openScoreModal('${p.id}')">
                    <i class="fas fa-${participantHasScores ? 'edit' : 'plus'}"></i>
                </button>
            </td>
        </tr>
    `;
    }).join('');
}

function openScoreModal(participantId) {
    const participant = getParticipantById(participantId);
    if (!participant) return;

    const modal = document.getElementById('scoreModal');

    // Show participant info
    document.getElementById('modalParticipantInfo').innerHTML = `
        <p><strong>ID:</strong> ${participant.id}</p>
        <p><strong>Nama:</strong> ${participant.name}</p>
        <p><strong>Pasukan:</strong> ${participant.team}</p>
        <p><strong>Acara:</strong> ${participant.eventType} (${participant.gender})</p>
    `;

    // Set current scores
    document.getElementById('editParticipantId').value = participant.id;
    document.getElementById('editG1').value = participant.scores.g1;
    document.getElementById('editG2').value = participant.scores.g2;
    document.getElementById('editG3').value = participant.scores.g3;
    document.getElementById('editG4').value = participant.scores.g4;
    document.getElementById('editG5').value = participant.scores.g5;

    // Update summary
    document.getElementById('editTotalScore').textContent = participant.total;
    document.getElementById('editAvgScore').textContent = participant.average;

    modal.classList.add('active');
}

// Make function global for onclick handlers
window.openScoreModal = openScoreModal;

// ========================================
// INITIALIZATION
// ========================================

document.addEventListener('DOMContentLoaded', () => {
    initializeData();
    // Navigation handled by Laravel routes
    // Registration form handled by Laravel (see registration.blade.php)
    initLeaderboardTabs();
    initAdminPanel();
    renderLeaderboard();
});
