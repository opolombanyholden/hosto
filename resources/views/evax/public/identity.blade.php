<!DOCTYPE html>
<html lang="fr"><head><meta charset="UTF-8"><title>Identité HOSTO</title>
<style>body{font-family:-apple-system,system-ui,sans-serif;max-width:480px;margin:60px auto;padding:24px;text-align:center;color:#1B2A1B;}</style>
</head><body>
<h2 style="margin:0;">{{ $user->name }}</h2>
@if($user->nip)<div style="color:#757575;margin-top:4px;">NIP : {{ $user->nip }}</div>@endif
@if($user->date_of_birth)<div style="color:#757575;">Né(e) le {{ $user->date_of_birth->format('d/m/Y') }}</div>@endif
<p style="margin-top:24px;font-size:.85rem;color:#757575;">
Cette page sert d'identification pour qu'un professionnel de santé puisse ajouter une vaccination à votre carnet.
</p>
</body></html>
