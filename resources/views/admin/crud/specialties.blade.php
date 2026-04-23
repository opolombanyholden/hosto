@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Specialites') @section('page-title', 'Specialites medicales') @section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar') @endsection

@section('content')
<div id="crudMsg" style="display:none;padding:12px;border-radius:10px;font-size:.82rem;margin-bottom:16px;"></div>

<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:20px;">
    <h3 style="font-size:.88rem;font-weight:600;color:#B71C1C;margin-bottom:12px;">Ajouter une specialite</h3>
    <div style="display:grid;grid-template-columns:1fr 2fr 2fr 1.5fr 80px 100px;gap:10px;align-items:end;">
        <div><label class="crud-label">Code *</label><input type="text" id="newCode" class="crud-input" placeholder="CARD"></div>
        <div><label class="crud-label">Nom FR *</label><input type="text" id="newNameFr" class="crud-input" placeholder="Cardiologie"></div>
        <div><label class="crud-label">Nom EN</label><input type="text" id="newNameEn" class="crud-input" placeholder="Cardiology"></div>
        <div><label class="crud-label">Parent</label>
            <select id="newParent" class="crud-input">
                <option value="">— Aucun (racine) —</option>
                @foreach($items as $parent)<option value="{{ $parent->id }}">{{ $parent->name_fr }}</option>@endforeach
            </select>
        </div>
        <div><label class="crud-label">Ordre</label><input type="number" id="newOrder" class="crud-input" value="0" min="0"></div>
        <button onclick="createItem()" class="crud-btn crud-btn-primary">Ajouter</button>
    </div>
</div>

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table class="crud-table">
        <thead><tr><th>Code</th><th>Nom FR</th><th>Nom EN</th><th>Ordre</th><th>Actif</th><th>Actions</th></tr></thead>
        <tbody>
            @foreach($items as $parent)
            <tr id="row-{{ $parent->id }}" style="background:#FAFAFA;">
                <td><code style="font-size:.78rem;background:#E3F2FD;padding:2px 6px;border-radius:4px;color:#1565C0;">{{ $parent->code }}</code></td>
                <td><input type="text" class="crud-input" value="{{ $parent->name_fr }}" data-field="name_fr" style="font-weight:600;"></td>
                <td><input type="text" class="crud-input" value="{{ $parent->name_en }}" data-field="name_en"></td>
                <td><input type="number" class="crud-input" value="{{ $parent->display_order }}" data-field="display_order" style="width:60px;" min="0"></td>
                <td><input type="checkbox" {{ $parent->is_active ? 'checked' : '' }} data-field="is_active" style="accent-color:#B71C1C;"></td>
                <td><button onclick="saveItem({{ $parent->id }})" class="crud-btn crud-btn-sm">Sauver</button></td>
            </tr>
            @foreach($parent->children as $child)
            <tr id="row-{{ $child->id }}">
                <td style="padding-left:32px;"><code style="font-size:.72rem;background:#F5F5F5;padding:2px 6px;border-radius:4px;">{{ $child->code }}</code></td>
                <td style="padding-left:32px;"><input type="text" class="crud-input" value="{{ $child->name_fr }}" data-field="name_fr"></td>
                <td><input type="text" class="crud-input" value="{{ $child->name_en }}" data-field="name_en"></td>
                <td><input type="number" class="crud-input" value="{{ $child->display_order }}" data-field="display_order" style="width:60px;" min="0"></td>
                <td><input type="checkbox" {{ $child->is_active ? 'checked' : '' }} data-field="is_active" style="accent-color:#B71C1C;"></td>
                <td><button onclick="saveItem({{ $child->id }})" class="crud-btn crud-btn-sm">Sauver</button></td>
            </tr>
            @endforeach
            @endforeach
        </tbody>
    </table>
</div>

@include('admin.crud.partials.crud-styles')
<script>
const CSRF = '{{ csrf_token() }}';
const headers = {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF,'X-Requested-With':'XMLHttpRequest'};
function showMsg(ok, text) { const el = document.getElementById('crudMsg'); el.style.display='block'; el.style.background=ok?'#E8F5E9':'#FFEBEE'; el.style.color=ok?'#2E7D32':'#C62828'; el.textContent=text; if(ok)setTimeout(()=>el.style.display='none',3000); }

async function createItem() {
    const body = { code:document.getElementById('newCode').value, name_fr:document.getElementById('newNameFr').value, name_en:document.getElementById('newNameEn').value||null, parent_id:document.getElementById('newParent').value||null, display_order:parseInt(document.getElementById('newOrder').value)||0 };
    if(!body.code||!body.name_fr){showMsg(false,'Code et nom FR obligatoires.');return;}
    try { const res=await fetch('/admin/specialties',{method:'POST',headers,body:JSON.stringify(body)}); const data=await res.json(); if(res.ok){showMsg(true,data.data?.message);setTimeout(()=>location.reload(),800);}else showMsg(false,data.errors?Object.values(data.errors).flat().join(' '):(data.error?.message||'Erreur.')); } catch(e){showMsg(false,'Erreur.');}
}

async function saveItem(id) {
    const row = document.getElementById('row-'+id);
    const body = { name_fr:row.querySelector('[data-field="name_fr"]').value, name_en:row.querySelector('[data-field="name_en"]').value||null, display_order:parseInt(row.querySelector('[data-field="display_order"]').value)||0, is_active:row.querySelector('[data-field="is_active"]').checked };
    try { const res=await fetch(`/admin/specialties/${id}`,{method:'PUT',headers,body:JSON.stringify(body)}); const data=await res.json(); showMsg(res.ok,data.data?.message||data.error?.message||'Erreur.'); } catch(e){showMsg(false,'Erreur.');}
}
</script>
@endsection
