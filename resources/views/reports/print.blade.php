<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - TAMS</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: Arial, sans-serif; color:#111827; font-size:12px; margin:0; padding:16px; }
        .kop { display:flex; align-items:center; gap:12px; border-bottom:3px solid #1E40AF; padding-bottom:10px; margin-bottom:14px; }
        .kop img { width:48px; height:48px; }
        .kop h1 { font-size:18px; margin:0; letter-spacing:1px; }
        .kop p { margin:2px 0 0; color:#64748B; }
        h2 { text-align:center; font-size:16px; margin:0 0 4px; }
        .meta { text-align:center; color:#475569; margin-bottom:12px; }
        .bar { margin-bottom:12px; }
        .bar button { padding:6px 14px; margin-right:6px; cursor:pointer; }
        @media print { .no-print { display:none; } body { padding:0; } }
    </style>
    @include('reports._style')
</head>
<body>
    <div class="bar no-print">
        <button onclick="window.print()">Cetak</button>
        <button onclick="window.close()">Tutup</button>
    </div>

    <div class="kop">
        <img src="{{ asset('images/logo-icon.svg') }}" alt="Logo TAMS">
        <div>
            <h1>TAMS</h1>
            <p>Technolife Assets Management System</p>
        </div>
    </div>

    <h2>{{ $title }}</h2>
    <div class="meta">
        @if ($filters)
            @foreach ($filters as $k => $v) {{ $k }}: <strong>{{ $v }}</strong>@if (! $loop->last) &middot; @endif @endforeach
            <br>
        @endif
        Dicetak pada {{ now()->format('d M Y H:i') }} oleh {{ auth()->user()->name }}
    </div>

    @include($type === 'aset' ? 'reports._table_assets' : 'reports._table_loans')

    <script>
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });
    </script>
</body>
</html>