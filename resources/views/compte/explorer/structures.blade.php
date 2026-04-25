@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Structures de sante') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'structures']) @endsection

@php
    $pageTitle = 'Structures de sante';
    $pageDesc = 'Trouvez un hopital, une pharmacie, un laboratoire pres de chez vous.';
    $pageIcon = '/images/icons/icon-hopitaux.png';
    $defaultType = '';
    $forceGarde = false;
    $markerColor = '#388E3C';
    $alertHtml = '';
@endphp

@include('compte.explorer.partials.structures-base')
