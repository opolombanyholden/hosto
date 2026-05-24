@php
    $impersonationSvc = app(\App\Modules\Core\Services\ImpersonationService::class);
    $impSession = $impersonationSvc->currentSession();
@endphp
@if($impSession)
<div style="background:#FFF3E0;border-bottom:2px solid #E65100;padding:10px 16px;text-align:center;font-size:.85rem;color:#E65100;font-weight:600;">
    &#9888; Vous êtes connecté en tant que <strong>{{ auth()->user()->name }}</strong>
    &mdash; <a href="/stop-impersonate" style="color:#E65100;text-decoration:underline;">Arrêter l'impersonation</a>
</div>
@endif
