<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #2E2925; }
        h1 { font-size: 16px; margin: 0 0 2px; }
        h2 { font-size: 12px; margin: 16px 0 6px; color: #8A6A4F; }
        .sub { color: #8A6A4F; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th { background: #F4EEE6; text-align: left; padding: 4px; border-bottom: 1px solid #D9C2A7; }
        td { padding: 3px 4px; border-bottom: 1px solid #eee; }
        .num { text-align: right; white-space: nowrap; }
    </style>
</head>
<body>
    <h1>{{ \App\Support\Setting::get('nome_salao') }} — {{ $titulo }}</h1>
    <div class="sub">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
    @foreach ($relatorios as $r)
        @if (count($relatorios) > 1)<h2>{{ $r->titulo }}</h2>@endif
        <table>
            <thead><tr>@foreach ($r->colunas as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse ($r->linhas as $linha)
                    <tr>
                        @foreach ($linha as $v)
                            <td class="{{ is_float($v) ? 'num' : '' }}">{{ is_float($v) ? \App\Support\Dinheiro::formatar($v) : $v }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ count($r->colunas) }}">Sem dados.</td></tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
