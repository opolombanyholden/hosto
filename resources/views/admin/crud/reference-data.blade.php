@extends('layouts.dashboard')

@section('env-name', 'HOSTO Admin')
@section('env-color', '#B71C1C')
@section('env-color-dark', '#880E0E')
@section('title', $categoryLabel)
@section('page-title', $categoryLabel)
@section('user-role', 'Administrateur')

@section('sidebar-nav')
@include('admin.partials.sidebar')
@endsection

@section('content')
<div id="crudMsg" style="display:none;padding:12px;border-radius:10px;font-size:.82rem;margin-bottom:16px;"></div>

{{-- Add form --}}
<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:20px;">
    <h3 style="font-size:.88rem;font-weight:600;color:#B71C1C;margin-bottom:12px;">Ajouter une entree</h3>
    <div style="display:grid;grid-template-columns:1fr 2fr 2fr 80px 100px;gap:10px;align-items:end;">
        <div><label style="font-size:.75rem;font-weight:500;display:block;margin-bottom:4px;">Code *</label><input type="text" id="newCode" class="crud-input" placeholder="code"></div>
        <div><label style="font-size:.75rem;font-weight:500;display:block;margin-bottom:4px;">Label FR *</label><input type="text" id="newLabelFr" class="crud-input" placeholder="Libelle francais"></div>
        <div><label style="font-size:.75rem;font-weight:500;display:block;margin-bottom:4px;">Label EN</label><input type="text" id="newLabelEn" class="crud-input" placeholder="English label"></div>
        <div><label style="font-size:.75rem;font-weight:500;display:block;margin-bottom:4px;">Ordre</label><input type="number" id="newOrder" class="crud-input" value="0" min="0"></div>
        <button onclick="createItem()" class="crud-btn crud-btn-primary">Ajouter</button>
    </div>
</div>

{{-- List --}}
<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table class="crud-table">
        <thead>
            <tr><th>Code</th><th>Label FR</th><th>Label EN</th><th>Ordre</th><th>Actif</th><th>Actions</th></tr>
        </thead>
        <tbody id="itemsList">
            @foreach($items as $item)
            <tr id="row-{{ $item->id }}">
                <td><code style="font-size:.78rem;background:#F5F5F5;padding:2px 6px;border-radius:4px;">{{ $item->code }}</code></td>
                <td><input type="text" class="crud-input" value="{{ $item->label_fr }}" data-field="label_fr" data-id="{{ $item->id }}"></td>
                <td><input type="text" class="crud-input" value="{{ $item->label_en }}" data-field="label_en" data-id="{{ $item->id }}"></td>
                <td><input type="number" class="crud-input" value="{{ $item->display_order }}" data-field="display_order" data-id="{{ $item->id }}" style="width:60px;" min="0"></td>
                <td><input type="checkbox" {{ $item->is_active ? 'checked' : '' }} onchange="toggleActive({{ $item->id }}, this.checked)" style="accent-color:#B71C1C;"></td>
                <td>
                    <button onclick="saveItem({{ $item->id }})" class="crud-btn crud-btn-sm">Sauver</button>
                    <button onclick="deleteItem({{ $item->id }})" class="crud-btn crud-btn-sm crud-btn-danger">Suppr</button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @if($items->isEmpty())
    <div style="text-align:center;padding:32px;color:#757575;font-size:.85rem;">Aucune entree.</div>
    @endif
</div>

{{-- Categories nav --}}
<div style="margin-top:24px;">
    <h4 style="font-size:.82rem;font-weight:600;color:#424242;margin-bottom:8px;">Autres categories</h4>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
        @foreach($categories as $catCode => $catLabel)
        <a href="/admin/references/{{ $catCode }}" style="padding:5px 12px;border-radius:100px;font-size:.72rem;font-weight:500;text-decoration:none;{{ $catCode === $category ? 'background:#B71C1C;color:white;' : 'background:#F5F5F5;color:#424242;' }}">{{ $catLabel }}</a>
        @endforeach
    </div>
</div>

<style>
    .crud-input { padding:8px 10px;border:1px solid #E0E0E0;border-radius:6px;font-family:Poppins,sans-serif;font-size:.82rem;width:100%;box-sizing:border-box;outline:none; }
    .crud-input:focus { border-color:#B71C1C; }
    .crud-btn { padding:8px 14px;border:none;border-radius:6px;font-family:Poppins,sans-serif;font-size:.78rem;font-weight:600;cursor:pointer; }
    .crud-btn-primary { background:#B71C1C;color:white; }
    .crud-btn-primary:hover { background:#880E0E; }
    .crud-btn-sm { padding:5px 10px;font-size:.72rem; background:#F5F5F5;color:#424242; }
    .crud-btn-sm:hover { background:#E0E0E0; }
    .crud-btn-danger { color:#E53935; }
    .crud-btn-danger:hover { background:#FFEBEE; }
    .crud-table { width:100%;border-collapse:collapse; }
    .crud-table th { text-align:left;padding:12px 16px;font-size:.75rem;font-weight:600;color:#757575;text-transform:uppercase;letter-spacing:.5px;border-bottom:2px solid #EEE;background:#FAFAFA; }
    .crud-table td { padding:10px 16px;border-bottom:1px solid #F5F5F5;font-size:.82rem; }
    .crud-table tr:hover { background:#FAFAFA; }
</style>

<script>
const CSRF = '{{ csrf_token() }}';
const CAT = '{{ $category }}';
const headers = {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'};

function showMsg(ok, text) {
    const el = document.getElementById('crudMsg');
    el.style.display = 'block';
    el.style.background = ok ? '#E8F5E9' : '#FFEBEE';
    el.style.color = ok ? '#2E7D32' : '#C62828';
    el.textContent = text;
    if (ok) setTimeout(() => el.style.display = 'none', 3000);
}

async function createItem() {
    const body = {
        code: document.getElementById('newCode').value,
        label_fr: document.getElementById('newLabelFr').value,
        label_en: document.getElementById('newLabelEn').value || null,
        display_order: parseInt(document.getElementById('newOrder').value) || 0,
    };
    if (!body.code || !body.label_fr) { showMsg(false, 'Code et label FR obligatoires.'); return; }
    try {
        const res = await fetch(`/admin/references/${CAT}`, { method:'POST', headers, body:JSON.stringify(body) });
        const data = await res.json();
        if (res.ok) { showMsg(true, data.data?.message); setTimeout(() => location.reload(), 800); }
        else showMsg(false, data.error?.message || 'Erreur.');
    } catch(e) { showMsg(false, 'Erreur.'); }
}

async function saveItem(id) {
    const row = document.getElementById('row-' + id);
    const body = {
        label_fr: row.querySelector('[data-field="label_fr"]').value,
        label_en: row.querySelector('[data-field="label_en"]').value || null,
        display_order: parseInt(row.querySelector('[data-field="display_order"]').value) || 0,
    };
    try {
        const res = await fetch(`/admin/references/item/${id}`, { method:'PUT', headers, body:JSON.stringify(body) });
        const data = await res.json();
        showMsg(res.ok, data.data?.message || data.error?.message || 'Erreur.');
    } catch(e) { showMsg(false, 'Erreur.'); }
}

async function toggleActive(id, active) {
    try {
        await fetch(`/admin/references/item/${id}`, { method:'PUT', headers, body:JSON.stringify({label_fr: document.querySelector(`#row-${id} [data-field="label_fr"]`).value, is_active: active}) });
    } catch(e) {}
}

async function deleteItem(id) {
    if (!confirm('Supprimer cette entree ?')) return;
    try {
        const res = await fetch(`/admin/references/item/${id}`, { method:'DELETE', headers });
        if (res.ok) { document.getElementById('row-' + id).remove(); showMsg(true, 'Supprime.'); }
        else showMsg(false, 'Erreur.');
    } catch(e) { showMsg(false, 'Erreur.'); }
}
</script>
@endsection
