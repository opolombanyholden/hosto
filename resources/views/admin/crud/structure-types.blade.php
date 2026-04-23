@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', 'Types de structure') @section('page-title', 'Types de structure') @section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar') @endsection

@section('content')
<div id="crudMsg" style="display:none;padding:12px;border-radius:10px;font-size:.82rem;margin-bottom:16px;"></div>

<div style="background:white;border:1px solid #EEE;border-radius:14px;padding:20px;margin-bottom:20px;">
    <h3 style="font-size:.88rem;font-weight:600;color:#B71C1C;margin-bottom:12px;">Ajouter un type</h3>
    <div style="display:grid;grid-template-columns:1fr 2fr 2fr 1fr 80px 100px;gap:10px;align-items:end;">
        <div><label class="crud-label">Slug *</label><input type="text" id="newSlug" class="crud-input" placeholder="hopital"></div>
        <div><label class="crud-label">Nom FR *</label><input type="text" id="newNameFr" class="crud-input" placeholder="Hopital"></div>
        <div><label class="crud-label">Nom EN</label><input type="text" id="newNameEn" class="crud-input" placeholder="Hospital"></div>
        <div><label class="crud-label">Icone</label><input type="text" id="newIcon" class="crud-input" placeholder="icon-hopital"></div>
        <div><label class="crud-label">Ordre</label><input type="number" id="newOrder" class="crud-input" value="0" min="0"></div>
        <button onclick="createItem()" class="crud-btn crud-btn-primary">Ajouter</button>
    </div>
</div>

<div style="background:white;border:1px solid #EEE;border-radius:14px;overflow:hidden;">
    <table class="crud-table">
        <thead><tr><th>Slug</th><th>Nom FR</th><th>Nom EN</th><th>Icone</th><th>Ordre</th><th>Actif</th><th>Actions</th></tr></thead>
        <tbody>
            @foreach($items as $item)
            <tr id="row-{{ $item->id }}">
                <td><code style="font-size:.78rem;background:#F5F5F5;padding:2px 6px;border-radius:4px;">{{ $item->slug }}</code></td>
                <td><input type="text" class="crud-input" value="{{ $item->name_fr }}" data-field="name_fr"></td>
                <td><input type="text" class="crud-input" value="{{ $item->name_en }}" data-field="name_en"></td>
                <td><input type="text" class="crud-input" value="{{ $item->icon }}" data-field="icon" style="width:120px;"></td>
                <td><input type="number" class="crud-input" value="{{ $item->display_order }}" data-field="display_order" style="width:60px;" min="0"></td>
                <td><input type="checkbox" {{ $item->is_active ? 'checked' : '' }} data-field="is_active" style="accent-color:#B71C1C;"></td>
                <td><button onclick="saveItem({{ $item->id }})" class="crud-btn crud-btn-sm">Sauver</button></td>
            </tr>
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
    const body = { slug:document.getElementById('newSlug').value, name_fr:document.getElementById('newNameFr').value, name_en:document.getElementById('newNameEn').value||null, icon:document.getElementById('newIcon').value||null, display_order:parseInt(document.getElementById('newOrder').value)||0 };
    if(!body.slug||!body.name_fr){showMsg(false,'Slug et nom FR obligatoires.');return;}
    try { const res=await fetch('/admin/structure-types',{method:'POST',headers,body:JSON.stringify(body)}); const data=await res.json(); if(res.ok){showMsg(true,data.data?.message);setTimeout(()=>location.reload(),800);}else showMsg(false,data.error?.message||data.errors?Object.values(data.errors).flat().join(' '):'Erreur.'); } catch(e){showMsg(false,'Erreur.');}
}

async function saveItem(id) {
    const row = document.getElementById('row-'+id);
    const body = { name_fr:row.querySelector('[data-field="name_fr"]').value, name_en:row.querySelector('[data-field="name_en"]').value||null, icon:row.querySelector('[data-field="icon"]').value||null, display_order:parseInt(row.querySelector('[data-field="display_order"]').value)||0, is_active:row.querySelector('[data-field="is_active"]').checked };
    try { const res=await fetch(`/admin/structure-types/${id}`,{method:'PUT',headers,body:JSON.stringify(body)}); const data=await res.json(); showMsg(res.ok,data.data?.message||data.error?.message||'Erreur.'); } catch(e){showMsg(false,'Erreur.');}
}
</script>
@endsection
