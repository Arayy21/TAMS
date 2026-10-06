<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 28px 30px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #121212; }
        .kop { width: 100%; border-collapse: collapse; border-bottom: 3px solid #B1002C; margin-bottom: 12px; }
        .kop td { border: 0; padding: 0 0 8px; vertical-align: middle; }
        .logo { width: 40px; height: 40px; background: #B1002C; color: #fff; border-radius: 10px;
                text-align: center; font-size: 22px; font-weight: bold; line-height: 40px; }
        .brand { font-size: 17px; font-weight: bold; letter-spacing: 1px; }
        .sub { color: #5F5E5E }
        h2 { text-align: center; font-size: 14px; margin: 0 0 4px; }
        .meta { text-align: center; color: #4A4A4A; margin-bottom: 10px; }
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

    @include('reports._table_' . ['aset' => 'assets', 'peminjaman' => 'loans', 'opname' => 'opname'][$type])
</body>
</html>