@extends('layouts.app')

@section('title', 'Utama')

@section('content')
<section class="section active">
    <div class="hero">
        <h1>Sistem Kejohanan Boling Ukhuwah Strike Challenge</h1>
        <p>Sistem pengurusan pendaftaran peserta dan papan kedudukan kejohanan boling</p>
        <div class="hero-buttons">
            <a href="{{ route('registration') }}" class="btn btn-primary">Daftar Sekarang</a>
            <a href="{{ route('leaderboard') }}" class="btn btn-secondary">Lihat Kedudukan</a>
        </div>
    </div>

    <div class="info-cards">
        <div class="info-card">
            <i class="fas fa-users"></i>
            <h3>Peserta</h3>
            <p>Daftar untuk kejohanan. Skor akan dimasukkan oleh admin.</p>
        </div>
        <div class="info-card">
            <i class="fas fa-trophy"></i>
            <h3>Kedudukan</h3>
            <p>Lihat papan kedudukan terkini dalam masa nyata</p>
        </div>
        <div class="info-card">
            <i class="fas fa-chart-bar"></i>
            <h4>Acara</h4>
            <p>Individu, Beregu, Trio, dan Berkumpulan</p>
        </div>
    </div>
</section>
@endsection
