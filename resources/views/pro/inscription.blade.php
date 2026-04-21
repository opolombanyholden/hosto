@extends('layouts.auth')

@section('color-main', '#1565C0')
@section('color-dark', '#0D47A1')
@section('logo-text', 'HOSTO Pro')
@section('robots', 'noindex')
@section('side-title', 'Rejoignez HOSTO Pro')
@section('side-description', 'Inscrivez-vous en tant que professionnel de sante. Votre compte sera valide par nos equipes avant activation.')

@section('form')
<h1>Inscription professionnelle</h1>
<p class="subtitle">Creez votre compte professionnel</p>

@if($errors->any())
<div class="auth-alert auth-alert-error">
    @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
</div>
@endif

<form method="POST" action="/pro/inscription">
    @csrf
    <div class="field">
        <label for="name">Nom complet</label>
        <input type="text" id="name" name="name" value="{{ old('name') }}" required autofocus>
    </div>
    <div class="field">
        <label for="email">Adresse email professionnelle</label>
        <input type="email" id="email" name="email" value="{{ old('email') }}" required>
    </div>
    <div class="field">
        <label for="phone">Telephone professionnel</label>
        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="+241..." required>
    </div>
    <div class="field">
        <label for="role">Votre profession</label>
        <select id="role" name="role" required>
            <option value="">Selectionnez votre profession</option>
            @foreach($roles as $role)
                <option value="{{ $role->slug }}" {{ old('role') === $role->slug ? 'selected' : '' }}>{{ $role->name_fr }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="password">Mot de passe</label>
        <div style="position:relative;">
            <input type="password" id="password" name="password" required oninput="checkPassword(this.value)">
            <button type="button" onclick="togglePassword('password')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#757575;font-size:.82rem;">Afficher</button>
        </div>
        <div style="margin-top:8px;">
            <div style="display:flex;gap:4px;margin-bottom:6px;">
                <div id="bar1" style="flex:1;height:4px;border-radius:2px;background:#EEE;transition:background .3s;"></div>
                <div id="bar2" style="flex:1;height:4px;border-radius:2px;background:#EEE;transition:background .3s;"></div>
                <div id="bar3" style="flex:1;height:4px;border-radius:2px;background:#EEE;transition:background .3s;"></div>
                <div id="bar4" style="flex:1;height:4px;border-radius:2px;background:#EEE;transition:background .3s;"></div>
            </div>
            <div id="strengthLabel" style="font-size:.72rem;color:#757575;"></div>
        </div>
        <div style="margin-top:10px;display:flex;flex-direction:column;gap:4px;">
            <div class="pw-check" id="chkLength"><span class="pw-icon">&#9675;</span><span>12 caracteres minimum</span></div>
            <div class="pw-check" id="chkUpper"><span class="pw-icon">&#9675;</span><span>Une lettre majuscule</span></div>
            <div class="pw-check" id="chkLower"><span class="pw-icon">&#9675;</span><span>Une lettre minuscule</span></div>
            <div class="pw-check" id="chkNumber"><span class="pw-icon">&#9675;</span><span>Un chiffre</span></div>
            <div class="pw-check" id="chkSpecial"><span class="pw-icon">&#9675;</span><span>Un caractere special (@, #, !, ...)</span></div>
        </div>
    </div>
    <div class="field">
        <label for="password_confirmation">Confirmer le mot de passe</label>
        <div style="position:relative;">
            <input type="password" id="password_confirmation" name="password_confirmation" required oninput="checkMatch()">
            <button type="button" onclick="togglePassword('password_confirmation')" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#757575;font-size:.82rem;">Afficher</button>
        </div>
        <div id="matchMsg" style="font-size:.72rem;margin-top:4px;display:none;"></div>
    </div>
    <button type="submit" class="auth-btn">Creer mon compte professionnel</button>
</form>

<p class="auth-link">Deja inscrit ? <a href="/pro/connexion">Se connecter</a></p>

<style>
    .pw-check { display:flex; align-items:center; gap:6px; font-size:.72rem; color:#757575; transition:color .2s; }
    .pw-check.valid { color:#2E7D32; }
    .pw-check.valid .pw-icon { color:#4CAF50; }
    .pw-icon { font-size:.82rem; transition:color .2s; }
</style>
<script>
function checkPassword(pw) {
    const checks = { length:pw.length>=12, upper:/[A-Z]/.test(pw), lower:/[a-z]/.test(pw), number:/[0-9]/.test(pw), special:/[^A-Za-z0-9]/.test(pw) };
    setCriteria('chkLength',checks.length); setCriteria('chkUpper',checks.upper); setCriteria('chkLower',checks.lower); setCriteria('chkNumber',checks.number); setCriteria('chkSpecial',checks.special);
    const passed = Object.values(checks).filter(Boolean).length;
    const score = !checks.length ? Math.min(passed,1) : passed;
    const colors=['#E53935','#FF9800','#FFC107','#8BC34A','#4CAF50'], labels=['Tres faible','Faible','Moyen','Bon','Excellent'];
    ['bar1','bar2','bar3','bar4'].forEach((id,i) => { document.getElementById(id).style.background = i<score ? colors[score] : '#EEE'; });
    const l=document.getElementById('strengthLabel'); if(!pw.length){l.textContent=''}else{l.textContent=labels[score];l.style.color=colors[score];}
    checkMatch();
}
function setCriteria(id,v){const e=document.getElementById(id);e.classList.toggle('valid',v);e.querySelector('.pw-icon').innerHTML=v?'&#10003;':'&#9675;';}
function checkMatch(){const pw=document.getElementById('password').value,c=document.getElementById('password_confirmation').value,m=document.getElementById('matchMsg');if(!c.length){m.style.display='none';return;}m.style.display='block';if(pw===c){m.textContent='Les mots de passe correspondent';m.style.color='#2E7D32';}else{m.textContent='Les mots de passe ne correspondent pas';m.style.color='#E53935';}}
function togglePassword(id){const i=document.getElementById(id),b=i.nextElementSibling;if(i.type==='password'){i.type='text';b.textContent='Masquer';}else{i.type='password';b.textContent='Afficher';}}
</script>
@endsection
