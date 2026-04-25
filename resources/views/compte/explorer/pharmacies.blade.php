@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Pharmacies') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'pharmacies']) @endsection

@php
    $pageTitle = 'Pharmacies';
    $pageDesc = 'Trouvez une pharmacie pres de chez vous.';
    $pageIcon = '/images/icons/icon-pharamcie.png';
    $defaultType = 'pharmacie';
    $forceGarde = false;
    $markerColor = '#388E3C';
    $alertHtml = '';
@endphp

@include('compte.explorer.partials.structures-base')
