@if(session('success'))
<div style="background:#E8F5E9;border:1px solid #81C784;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#2E7D32;">{{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:#FFEBEE;border:1px solid #EF9A9A;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#C62828;">{{ session('error') }}</div>
@endif
@if($errors->any())
<div style="background:#FFEBEE;border:1px solid #EF9A9A;border-radius:12px;padding:12px 16px;margin-bottom:16px;font-size:.85rem;color:#C62828;">{{ $errors->first() }}</div>
@endif
