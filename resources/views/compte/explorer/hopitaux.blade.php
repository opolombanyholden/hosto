@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Hopitaux et Cliniques') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'hopitaux']) @endsection

@php
    $pageTitle = 'Hopitaux et Cliniques';
    $pageDesc = 'Trouvez un hopital, une clinique ou un centre de sante.';
    $pageIcon = '/images/icons/icon-hopitaux.png';
    $defaultType = 'hopital';
    $forceGarde = false;
    $markerColor = '#388E3C';
    $alertHtml = '';
@endphp

@include('compte.explorer.partials.structures-base')
