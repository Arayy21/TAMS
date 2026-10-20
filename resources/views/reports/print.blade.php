<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - TAMS</title>
    <style>
        @page { size: A4 landscape; margin: 12mm; }
        body { font-family: Arial, sans-serif; color:#111827; font-size:12px; margin:0; padding:16px; }
        .kop { display:flex; align-items:center; gap:12px; border-bottom:3px solid #DC143C; padding-bottom:10px; margin-bottom:14px; }
        .kop img { width:48px; height:48px; }
        .kop h1 { font-size:18px; margin:0; letter-spacing:1px; }
        .kop p { margin:2px 0 0; color:#64748B; }
        h2 { text-align:center; font-size:16px; margin:0 0 4px; }
        .meta { text-align:center; color:#475569; margin-bottom:12px; }
        .bar { margin-bottom:12px; }
        .bar button { padding:6px 14px; margin-right:6px; cursor:pointer; }

        /* Lampiran foto bukti: satu halaman per peminjaman */
        .ev { page-break-before: always; break-before: page; }
        .ev h3 { font-size:15px; margin:0 0 6px; color:#DC143C; }
        .ev-warn { font-size:11px; color:#92400E; margin:0 0 6px; }
        .ev-none { color:#777; font-size:11px; }
        .evi { width:100%; border-collapse:collapse; margin-bottom:8px; }
        .evi td { border:1px solid #CBD5E1; padding:4px 8px; font-size:11px; }
        .evi td.k { background:#F1F5F9; font-weight:bold; width:13%; }
        .evh { font-weight:bold; font-size:12px; margin:8px 0 4px; }
        .evt { border-collapse:collapse; margin-bottom:4px; }
        .evt td { padding:0 6px 6px 0; vertical-align:top; }
        .evt img { height:120px; width:auto; border:1px solid #CBD5E1; display:block; }

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

    @include('reports._table_' . ['aset' => 'assets', 'peminjaman' => 'loans', 'opname' => 'opname', 'mutasi' => 'movements'][$type])

    @include('reports._evidence_print')

    <script>
        window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 400); });
    </script>
</body>
</html>