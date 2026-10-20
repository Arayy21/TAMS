<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #111827; }
        .kop { width: 100%; border-collapse: collapse; border-bottom: 3px solid #DC143C; margin-bottom: 12px; }
        .kop td { border: 0; padding: 0 0 8px; vertical-align: middle; }
        .logo { width: 40px; height: 40px; background: #DC143C; color: #fff; border-radius: 10px;
                text-align: center; font-size: 22px; font-weight: bold; line-height: 40px; }
        .brand { font-size: 17px; font-weight: bold; letter-spacing: 1px; }
        .sub { color: #64748B; }
        h2 { text-align: center; font-size: 14px; margin: 0 0 4px; }
        .meta { text-align: center; color: #475569; margin-bottom: 10px; }

        /* Lampiran foto bukti: satu halaman per peminjaman */
        .ev { page-break-before: always; }
        .ev h3 { font-size: 13px; margin: 0 0 4px; color: #DC143C; }
        .ev-warn { font-size: 9px; color: #92400E; margin-bottom: 4px; }
        .ev-none { font-size: 9px; color: #777777; }
        .evi { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .evi td { border: 1px solid #CBD5E1; padding: 3px 6px; font-size: 9px; }
        .evi td.k { background: #F1F5F9; font-weight: bold; width: 13%; }
        .evh { font-weight: bold; font-size: 10px; margin: 6px 0 3px; }
        .evt { width: 100%; border-collapse: collapse; }
        .evt td { width: 20%; text-align: left; vertical-align: top; padding: 0 3px 4px 0; border: 0; }
        .evt img { width: 150px; }
    </style>
    @include('reports._style')
    <style> .rpt { font-size: 9px; } </style>
</head>
<body>
    <table class="kop">
        <tr>
            <td style="width:50px"><div class="logo">T</div></td>
            <td>
                <div class="brand">TAMS</div>
                <div class="sub">Technolife Assets Management System</div>
            </td>
        </tr>
    </table>

    <h2>{{ $title }}</h2>
    <div class="meta">
        @if ($filters)
            @foreach ($filters as $k => $v){{ $k }}: <strong>{{ $v }}</strong>@if (! $loop->last) | @endif @endforeach
            <br>
        @endif
        Dicetak pada {{ now()->format('d M Y H:i') }} oleh {{ auth()->user()->name }}
    </div>

    @include('reports._table_' . ['aset' => 'assets', 'peminjaman' => 'loans', 'opname' => 'opname', 'mutasi' => 'movements'][$type])

    @include('reports._evidence_print')
</body>
</html>