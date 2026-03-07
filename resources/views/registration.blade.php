@extends('layouts.app')

@section('title', 'Pendaftaran')

@section('content')
<section class="section active">
    <h2 class="section-title">Borang Pendaftaran</h2>

    @if(session('success'))
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i>
            {{ session('error') }}
        </div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('registration.store') }}" method="POST" class="registration-form" id="registrationForm">
        @csrf

        <div class="form-section">
            <h3>Maklumat Peserta</h3>

            <div class="form-group">
                <label for="regName">Nama Penuh</label>
                <input type="text" id="regName" name="name" value="{{ old('name') }}" required>
            </div>

            <div class="form-group">
                <label for="regIC">No. Kad Pengenalan</label>
                <input type="text" id="regIC" name="ic" value="{{ old('ic') }}" required>
            </div>

            <div class="form-group">
                <label for="regPhone">No. Telefon</label>
                <input type="tel" id="regPhone" name="phone" value="{{ old('phone') }}" required>
            </div>

            <div class="form-group">
                <label for="regTeam">Nama Pasukan / Sekolah / PPD</label>
                <input type="text" id="regTeam" name="team" value="{{ old('team') }}" required>
            </div>

            <div class="form-group">
                <label for="regGender">Jantina</label>
                <select id="regGender" name="gender" required>
                    <option value="">Pilih...</option>
                    <option value="lelaki" {{ old('gender') == 'lelaki' ? 'selected' : '' }}>Lelaki</option>
                    <option value="wanita" {{ old('gender') == 'wanita' ? 'selected' : '' }}>Wanita</option>
                </select>
            </div>

            <div class="form-group">
                <label for="regEventType">Jenis Acara</label>
                <select id="regEventType" name="event_type" required>
                    <option value="">Pilih...</option>
                    <option value="individu" {{ old('event_type') == 'individu' ? 'selected' : '' }}>Individu</option>
                    <option value="beregu" {{ old('event_type') == 'beregu' ? 'selected' : '' }}>Beregu (2 orang)</option>
                    <option value="trio" {{ old('event_type') == 'trio' ? 'selected' : '' }}>Trio (3 orang)</option>
                    <option value="berkumpulan" {{ old('event_type') == 'berkumpulan' ? 'selected' : '' }}>Berkumpulan (6 orang)</option>
                </select>
            </div>
        </div>

        <!-- Team Members Section (hidden by default) -->
        <div class="form-section" id="teamMembersSection" style="display: none;">
            <h3>Ahli Pasukan Tambahan</h3>

            <div id="teamMembersContainer">
                <!-- Dynamic team member fields -->
            </div>
        </div>

        <p class="form-hint" style="text-align: center; margin: 2rem 0;">
            <i class="fas fa-info-circle"></i> Skor permainan akan dimasukkan oleh admin selepas pendaftaran.
        </p>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Daftar</button>
            <button type="reset" class="btn btn-secondary">Reset</button>
        </div>
    </form>
</section>
@endsection

@push('scripts')
<script>
    // Team member management
    const eventTypeSelect = document.getElementById('regEventType');
    const teamMembersSection = document.getElementById('teamMembersSection');
    const teamMembersContainer = document.getElementById('teamMembersContainer');

    eventTypeSelect.addEventListener('change', function() {
        const eventType = this.value;

        // Clear existing team members
        teamMembersContainer.innerHTML = '';

        if (eventType === 'beregu') {
            // Show 1 additional member field
            teamMembersSection.style.display = 'block';
            addTeamMemberFields(1);
        } else if (eventType === 'trio') {
            // Show 2 additional member fields
            teamMembersSection.style.display = 'block';
            addTeamMemberFields(2);
        } else if (eventType === 'berkumpulan') {
            // Show 5 additional member fields
            teamMembersSection.style.display = 'block';
            addTeamMemberFields(5);
        } else {
            // Hide for individual
            teamMembersSection.style.display = 'none';
        }
    });

    function addTeamMemberFields(count) {
        for (let i = 1; i <= count; i++) {
            const memberDiv = document.createElement('div');
            memberDiv.className = 'team-member-group';
            memberDiv.innerHTML = `
                <h4>Ahli Pasukan ${i + 1}</h4>
                <div class="form-group">
                    <label for="member_${i}_name">Nama Penuh</label>
                    <input type="text" id="member_${i}_name" name="team_members[${i}][name]" required>
                </div>
                <div class="form-group">
                    <label for="member_${i}_ic">No. Kad Pengenalan</label>
                    <input type="text" id="member_${i}_ic" name="team_members[${i}][ic]" required>
                </div>
            `;
            teamMembersContainer.appendChild(memberDiv);
        }
    }

    // Form submission handling
    document.getElementById('registrationForm').addEventListener('submit', function(e) {
        console.log('Form submitted');
        const eventType = document.getElementById('regEventType').value;
        console.log('Event type:', eventType);

        // Check if event type is selected
        if (!eventType) {
            e.preventDefault();
            alert('Sila pilih jenis acara');
            document.getElementById('regEventType').focus();
            return;
        }

        if (eventType === 'beregu' || eventType === 'trio' || eventType === 'berkumpulan') {
            const teamMemberFields = teamMembersContainer.querySelectorAll('input[type="text"]');
            let isValid = true;

            console.log('Checking team members, count:', teamMemberFields.length);
            teamMemberFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = 'red';
                    console.log('Invalid field:', field.id);
                } else {
                    field.style.borderColor = '';
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Sila isi semua maklumat ahli pasukan');
                return;
            }
        }

        console.log('Form validation passed, allowing submission');
        // Don't prevent default - let the form submit normally
    });

    // Reset button handler
    document.querySelector('button[type="reset"]').addEventListener('click', function() {
        teamMembersSection.style.display = 'none';
        teamMembersContainer.innerHTML = '';
    });
</script>
@endpush

@push('styles')
<style>
    .alert {
        padding: 1rem;
        margin-bottom: 1rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert ul {
        margin: 0;
        padding-left: 1.5rem;
    }

    .team-member-group {
        background: #f8f9fa;
        padding: 1.5rem;
        border-radius: 8px;
        margin-bottom: 1rem;
    }

    .team-member-group h4 {
        margin-bottom: 1rem;
        color: var(--primary-color);
    }
</style>
@endpush
