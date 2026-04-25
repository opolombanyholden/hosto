@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Soins a domicile') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'soins']) @endsection

@php
    $pageTitle = 'Soins a domicile';
    $pageDesc = 'Trouvez un professionnel pour des soins a domicile (infirmier, kinesitherapeute, sage-femme).';
    $pageIcon = '/images/icons/icon-soin-a-domicile.png';
    $defaultType = 'cabinet-medical';
    $forceGarde = false;
    $markerColor = '#E65100';
    $alertHtml = '<div style="background:#FFF3E0;border:1px solid #FFE082;border-radius:10px;padding:14px 16px;margin-bottom:16px;font-size:.82rem;color:#795548;display:flex;gap:8px;align-items:start;"><span style="font-size:1.1rem;">&#128161;</span><span>Les soins a domicile comprennent les injections, pansements, perfusions, kinesitherapie, suivi post-operatoire et soins de maternite.</span></div>';
@endphp

@include('compte.explorer.partials.structures-base')
