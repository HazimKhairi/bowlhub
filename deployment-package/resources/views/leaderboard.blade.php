@extends('layouts.app')

@section('title', 'Kedudukan - Ukhuwah Strike Challenge')

@section('content')
<section id="leaderboard" class="section active">
    <h2 class="section-title">Papan Kedudukan</h2>

    <!-- Event Tabs -->
    <div class="event-tabs">
        <button class="tab-btn active" data-event="individu">Individu</button>
        <button class="tab-btn" data-event="beregu">Beregu</button>
        <button class="tab-btn" data-event="trio">Trio</button>
        <button class="tab-btn" data-event="berkumpulan">Berkumpulan</button>
        <button class="tab-btn" data-event="pingat">Kedudukan Pingat</button>
    </div>

    <!-- Gender Tabs for Individual -->
    <div class="gender-tabs" id="genderTabs">
        <button class="gender-tab-btn active" data-gender="lelaki">Lelaki</button>
        <button class="gender-tab-btn" data-gender="wanita">Wanita</button>
    </div>

    <!-- Leaderboard Content -->
    <div class="leaderboard-content" id="leaderboardContent">
        <!-- Content will be loaded dynamically -->
        <div class="loading-state">
            <i class="fas fa-spinner fa-spin"></i>
            <p>Memuatkan data...</p>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Leaderboard state - use window to avoid conflicts with app.js
    window.leaderboardState = {
        currentEvent: 'individu',
        currentGender: 'lelaki'
    };

    // Initialize leaderboard tabs
    function initLeaderboardTabs() {
        const eventTabs = document.querySelectorAll('.tab-btn');
        const genderTabs = document.querySelectorAll('.gender-tab-btn');

        eventTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                eventTabs.forEach(t => t.classList.remove('active'));
                tab.classList.add('active');
                window.leaderboardState.currentEvent = tab.dataset.event;

                // Show/hide gender tabs
                const genderTabsContainer = document.getElementById('genderTabs');
                if (window.leaderboardState.currentEvent === 'pingat') {
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
                window.leaderboardState.currentGender = tab.dataset.gender;
                renderLeaderboard();
            });
        });
    }

    // Render leaderboard
    async function renderLeaderboard() {
        const content = document.getElementById('leaderboardContent');

        if (window.leaderboardState.currentEvent === 'pingat') {
            await renderMedalStandings(content);
            return;
        }

        content.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Memuatkan data...</p>
            </div>
        `;

        try {
            const response = await fetch(`/api/leaderboard/${window.leaderboardState.currentEvent}/${window.leaderboardState.currentGender}`);
            const data = await response.json();

            switch (window.leaderboardState.currentEvent) {
                case 'individu':
                    content.innerHTML = renderIndividualLeaderboard(data);
                    break;
                case 'beregu':
                    content.innerHTML = renderBereguLeaderboard(data);
                    break;
                case 'trio':
                    content.innerHTML = renderTrioLeaderboard(data);
                    break;
                case 'berkumpulan':
                    content.innerHTML = renderBerkumpulanLeaderboard(data);
                    break;
            }
        } catch (error) {
            console.error('Error loading leaderboard:', error);
            content.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Ralat memuatkan data. Sila cuba lagi.</p>
                </div>
            `;
        }
    }

    // Render medal standings
    async function renderMedalStandings(container) {
        container.innerHTML = `
            <div class="loading-state">
                <i class="fas fa-spinner fa-spin"></i>
                <p>Memuatkan data...</p>
            </div>
        `;

        try {
            const response = await fetch('/api/leaderboard/medal-standings');
            const data = await response.json();
            container.innerHTML = renderMedalStandingsTable(data);
        } catch (error) {
            console.error('Error loading medal standings:', error);
            container.innerHTML = `
                <div class="error-state">
                    <i class="fas fa-exclamation-triangle"></i>
                    <p>Ralat memuatkan data. Sila cuba lagi.</p>
                </div>
            `;
        }
    }

    // Render individual leaderboard
    function renderIndividualLeaderboard(data) {
        const participants = data.participants || [];
        const genderDisplay = data.gender === 'lelaki' ? 'Lelaki' : 'Wanita';

        // Section 1: Acara Individu
        let html = `
            <div class="leaderboard-section">
                <h3><i class="fas fa-user"></i> Acara Individu - ${genderDisplay}</h3>
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
                const highScore = Math.max(p.scores.g1, p.scores.g2, p.scores.g3, p.scores.g4, p.scores.g5);
                const diff = maxScore - highScore;
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
                <h3><i class="fas fa-trophy"></i> Pemenang Individu - ${genderDisplay}</h3>
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

        // Section 3: Jatuhan Pin Tertinggi
        html += `
            <div class="leaderboard-section">
                <h3><i class="fas fa-star"></i> Jatuhan Pin Tertinggi - ${genderDisplay}</h3>
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

    // Render beregu leaderboard
    function renderBereguLeaderboard(data) {
        const participants = data.participants || [];
        const genderDisplay = data.gender === 'lelaki' ? 'Lelaki' : 'Wanita';

        let html = `
            <div class="leaderboard-section">
                <h3><i class="fas fa-users"></i> Acara Beregu - ${genderDisplay}</h3>
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
                const member1 = p.name || '-';
                const member2 = p.teamMembers && p.teamMembers[0] ? p.teamMembers[0].name : '-';
                html += `
                    <tr>
                        <td>${member1}</td>
                        <td>${member2}</td>
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

    // Render trio leaderboard
    function renderTrioLeaderboard(data) {
        const participants = data.participants || [];
        const genderDisplay = data.gender === 'lelaki' ? 'Lelaki' : 'Wanita';

        let html = `
            <div class="leaderboard-section">
                <h3><i class="fas fa-users"></i> Acara Trio - ${genderDisplay}</h3>
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
                const member1 = p.name || '-';
                const member2 = p.teamMembers && p.teamMembers[0] ? p.teamMembers[0].name : '-';
                const member3 = p.teamMembers && p.teamMembers[1] ? p.teamMembers[1].name : '-';
                html += `
                    <tr>
                        <td>${member1}</td>
                        <td>${member2}</td>
                        <td>${member3}</td>
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

    // Render berkumpulan leaderboard
    function renderBerkumpulanLeaderboard(data) {
        const participants = data.participants || [];
        const genderDisplay = data.gender === 'lelaki' ? 'Lelaki' : 'Wanita';

        // Section 1: Acara Berkumpulan
        let html = `
            <div class="leaderboard-section">
                <h3><i class="fas fa-users"></i> Acara Berkumpulan - ${genderDisplay}</h3>
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th style="width: 25%">Pasukan</th>
                                <th style="width: 50%">Ahli Pasukan</th>
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
            html += `<tr><td colspan="10"><div class="empty-state"><i class="fas fa-inbox"></i><p>Tiada data dipaparkan</p></div></td></tr>`;
        } else {
            participants.forEach(p => {
                // Format team members list
                const membersList = p.teamMembers && p.teamMembers.length > 0
                    ? p.teamMembers.map(m => m.name).join(', ')
                    : '-';

                html += `
                    <tr>
                        <td><strong>${p.name}</strong></td>
                        <td class="team-members-cell">${membersList}</td>
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
                <h3><i class="fas fa-trophy"></i> Pemenang Berkumpulan - ${genderDisplay}</h3>
                <div class="data-table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Kedudukan</th>
                                <th style="width: 40%">Pasukan</th>
                                <th style="width: 40%">Ahli Pasukan</th>
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
                // Format team members list
                const membersList = p.teamMembers && p.teamMembers.length > 0
                    ? p.teamMembers.map(m => m.name).join(', ')
                    : '-';

                html += `
                    <tr>
                        <td><span class="rank-badge ${getRankClass(p.rank)}">${getRankLabel(p.rank)}</span></td>
                        <td><strong>${p.name}</strong></td>
                        <td class="team-members-cell">${membersList}</td>
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

    // Render medal standings table
    function renderMedalStandingsTable(data) {
        const standings = data.standings || [];

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

    // Helper functions
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

    // Initialize on page load - ensure it runs immediately
    (function() {
        console.log('Leaderboard page loaded, initializing...');

        // Initialize tabs
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM Content Loaded - initializing leaderboard');
                initLeaderboardTabs();
                renderLeaderboard();
            });
        } else {
            // DOM is already ready
            console.log('DOM already ready - initializing leaderboard immediately');
            initLeaderboardTabs();
            renderLeaderboard();
        }
    })();
</script>
@endpush
