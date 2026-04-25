@extends('layouts.dashboard')
@section('env-name', 'HOSTO') @section('env-color', '#388E3C') @section('env-color-dark', '#2E7D32')
@section('title', 'Urgences et Evacuation') @section('user-role', 'Patient')
@section('sidebar-nav') @include('compte.partials.sidebar', ['active' => 'urgences']) @endsection

@php
    $pageTitle = 'Urgences et Evacuation';
    $pageDesc = 'Structures avec services de garde, urgences et numeros d\'urgence.';
    $pageIcon = '/images/icons/icon-ambulance.png';
    $defaultType = '';
    $forceGarde = true;
    $markerColor = '#C62828';
    $alertHtml = '<div style="background:#FFEBEE;border:2px solid #EF9A9A;border-radius:14px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;gap:12px;"><div style="width:40px;height:40px;background:#C62828;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/></svg></div><div><div style="font-size:.85rem;font-weight:600;color:#B71C1C;">En cas d\'urgence vitale, appelez le 1300 ou le 011 76 22 44</div><div style="font-size:.78rem;color:#C62828;">SAMU / CHU de Libreville</div></div></div>';
@endphp

@include('compte.explorer.partials.structures-base')
