@extends('layouts.dashboard')
@section('env-name', 'HOSTO Admin') @section('env-color', '#B71C1C') @section('env-color-dark', '#880E0E')
@section('title', $category->exists ? 'Éditer catégorie' : 'Nouvelle catégorie')
@section('page-title', $category->exists ? 'Éditer : '.$category->name_fr : 'Nouvelle catégorie pro')
@section('user-role', 'Administrateur')
@section('sidebar-nav') @include('admin.partials.sidebar', ['active' => 'pro-cats']) @endsection

@section('content')
<form method="POST" action="{{ $category->exists ? '/admin/practitioner-categories/'.$category->uuid : '/admin/practitioner-categories' }}" style="max-width:600px;background:white;border:1px solid #EEE;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:12px;">
    @csrf
    @if($category->exists) @method('PUT') @endif

    <label>Code (slug)<input type="text" name="code" required value="{{ old('code', $category->code) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Nom (FR)<input type="text" name="name_fr" required value="{{ old('name_fr', $category->name_fr) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Nom (EN)<input type="text" name="name_en" value="{{ old('name_en', $category->name_en) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Description<textarea name="description_fr" rows="3" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">{{ old('description_fr', $category->description_fr) }}</textarea></label>
    <label>Icône (nom)<input type="text" name="icon_name" value="{{ old('icon_name', $category->icon_name) }}" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Couleur (hex)<input type="color" name="color_hex" value="{{ old('color_hex', $category->color_hex ?? '#388E3C') }}" style="width:80px;height:36px;border:1px solid #DDD;border-radius:6px;"></label>
    <label>Catégorie parente
        <select name="parent_category_id" style="width:100%;padding:8px 10px;border:1px solid #DDD;border-radius:6px;">
            <option value="">— Aucune —</option>
            @foreach($parents as $p)
                <option value="{{ $p->id }}" @if(old('parent_category_id', $category->parent_category_id) == $p->id) selected @endif>{{ $p->name_fr }}</option>
            @endforeach
        </select>
    </label>
    <label><input type="checkbox" name="is_medical" value="1" @if(old('is_medical', $category->is_medical ?? true)) checked @endif> Catégorie médicale</label>
    <label>Ordre d'affichage<input type="number" name="display_order" value="{{ old('display_order', $category->display_order ?? 0) }}" style="width:100px;padding:8px 10px;border:1px solid #DDD;border-radius:6px;"></label>
    <button type="submit" style="padding:10px;background:#B71C1C;color:white;border:none;border-radius:6px;font-weight:600;cursor:pointer;">Enregistrer</button>
</form>
@endsection
