{{-- Section service AJAX réutilisable --}}
{{-- Variables : $sectionId, $title, $category, $color --}}
<div class="section-block">
    <h3 style="color:{{ $color }};">{{ $title }}</h3>
    <div style="margin-bottom:10px;">
        <input type="text" id="{{ $sectionId }}Search" placeholder="Rechercher..." oninput="debounceSection('{{ $sectionId }}','{{ $category }}')" style="width:100%;padding:8px 12px;border:1px solid #EEE;border-radius:8px;font-family:Poppins,sans-serif;font-size:.82rem;outline:none;box-sizing:border-box;">
    </div>
    <div id="{{ $sectionId }}List"></div>
    <div id="{{ $sectionId }}Empty" style="display:none;text-align:center;padding:12px;color:#757575;font-size:.82rem;">Aucun resultat.</div>
    <div id="{{ $sectionId }}Pagination" style="display:flex;justify-content:center;gap:4px;padding:8px 0;"></div>
</div>
