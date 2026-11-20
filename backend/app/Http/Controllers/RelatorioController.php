<?php

namespace App\Http\Controllers;

use App\Models\Passagem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RelatorioController extends Controller
{
    private const FORMAS = [
        'dinheiro' => ['Dinheiro', '#18aa80'],
        'pix' => ['Pix', '#2f80ed'],
        'cartao' => ['Cartão', '#f2994a'],
    ];

    public function index(Request $request)
    {
        [$periodo, $inicio, $fim] = $this->periodo($request);

        return view('relatorios.index', $this->montar($request, $inicio, $fim) + [
            'periodo' => $periodo,
            'inicio' => $inicio,
            'fim' => $fim,
        ]);
    }

    /** Exporta as vendas e encomendas do período em CSV (abre direto no Excel) */
    public function exportar(Request $request)
    {
        [, $inicio, $fim] = $this->periodo($request);
        $linhas = $this->montar($request, $inicio, $fim)['linhas'];

        $nome = 'relatorio-rota-facil-' . $inicio->format('Ymd') . '-' . $fim->format('Ymd') . '.csv';

        // Evita que o Excel execute como fórmula um texto que começa com = + - @
        $seguro = fn (string $t) => preg_match('/^[=+\-@\t\r]/', $t) ? "'" . $t : $t;
        $moeda = fn ($v) => number_format((float) $v, 2, ',', '');

        return response()->streamDownload(function () use ($linhas, $seguro, $moeda) {
            $saida = fopen('php://output', 'w');
            fwrite($saida, "\xEF\xBB\xBF"); // acentos corretos no Excel

            fputcsv($saida, ['Data', 'Código', 'Passageiro / remetente', 'Rota', 'Serviço', 'Valor', 'Recebido', 'A receber', 'Situação'], ';', '"', '');

            foreach ($linhas as $l) {
                fputcsv($saida, [
                    $l['data']->format('d/m/Y H:i'),
                    $l['codigo'],
                    $seguro($l['nome']),
                    $seguro($l['rota']),
                    $l['servico'],
                    $moeda($l['valor']),
                    $moeda($l['recebido']),
                    $moeda($l['saldo']),
                    $l['situacao'],
                ], ';', '"', '');
            }

            fclose($saida);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function periodo(Request $request): array
    {
        $hoje = now();
        $fim = $hoje->copy()->endOfDay();
        $periodo = (string) $request->query('periodo', '30d');

        if (! in_array($periodo, ['7d', '30d', '6m', '1a', 'custom'], true)) {
            $periodo = '30d';
        }

        $inicio = match ($periodo) {
            '7d' => $hoje->copy()->subDays(6)->startOfDay(),
            '6m' => $hoje->copy()->subMonthsNoOverflow(6)->startOfDay(),
            '1a' => $hoje->copy()->subYearNoOverflow()->startOfDay(),
            default => $hoje->copy()->subDays(29)->startOfDay(),
        };

        if ($periodo === 'custom') {
            try {
                $de = Carbon::createFromFormat('Y-m-d', (string) $request->query('de'))->startOfDay();
                $ate = Carbon::createFromFormat('Y-m-d', (string) $request->query('ate'))->endOfDay();

                if ($ate->gte($de) && abs($de->diffInDays($ate)) <= 731) {
                    $inicio = $de;
                    $fim = $ate;
                }
            } catch (\Throwable) {
                // datas inválidas: mantém os últimos 30 dias
            }
        }

        return [$periodo, $inicio, $fim];
    }

    private function montar(Request $request, Carbon $inicio, Carbon $fim): array
    {
        $motorista = $request->user();
        $uid = $motorista->id;

        // Pagamentos do motorista: de passagens ativas das viagens dele e de encomendas dele
        $pagamentos = fn () => DB::table('pagamentos as pg')
            ->leftJoin('passagens as pa', 'pa.id', '=', 'pg.passagem_id')
            ->leftJoin('viagens as v', 'v.id', '=', 'pa.viagem_id')
            ->leftJoin('encomendas as e', 'e.id', '=', 'pg.encomenda_id')
            ->where(fn ($q) => $q
                ->where(fn ($q) => $q->where('v.user_id', $uid)->where('pa.situacao', 'ativa'))
                ->orWhere('e.user_id', $uid));

        $recebidoMes = (float) $pagamentos()
            ->where('pg.situacao', 'pago')
            ->whereBetween('pg.data_pagamento', [now()->startOfMonth(), now()])
            ->sum('pg.valor');

        $recebidoPeriodo = (float) $pagamentos()
            ->where('pg.situacao', 'pago')
            ->whereBetween('pg.data_pagamento', [$inicio, $fim])
            ->sum('pg.valor');

        $fiadoTotal = (float) $pagamentos()->where('pg.situacao', 'pendente')->sum('pg.valor');

        // Vendas do período (pela data em que foram registradas)
        $passagens = Passagem::whereHas('viagem', fn ($q) => $q->where('user_id', $uid))
            ->where('situacao', 'ativa')
            ->whereBetween('created_at', [$inicio, $fim])
            ->with(['viagem', 'passageiro', 'pagamentos'])
            ->get();

        $encomendas = $motorista->encomendas()
            ->whereBetween('created_at', [$inicio, $fim])
            ->with('pagamentos')
            ->get();

        $situacao = fn (string $r) => $r === 'A receber' ? 'Fiado' : $r;
        $classe = fn (string $r) => match ($r) {
            'Pago' => 'verde',
            'Pago parcialmente' => 'ciano',
            'A receber' => 'amarelo',
            default => '',
        };

        $linhas = collect();

        foreach ($passagens as $p) {
            $linhas->push([
                'data' => $p->created_at,
                'codigo' => $p->codigo,
                'nome' => $p->passageiro->nome,
                'rota' => $p->viagem->origem . ' · ' . $p->viagem->destino,
                'servico' => 'Passagem',
                'valor' => (float) $p->valor,
                'recebido' => (float) $p->recebido,
                'saldo' => (float) $p->saldo,
                'situacao' => $situacao($p->situacao_pagamento),
                'classe' => $classe($p->situacao_pagamento),
            ]);
        }

        foreach ($encomendas as $e) {
            $linhas->push([
                'data' => $e->created_at,
                'codigo' => $e->codigo,
                'nome' => $e->remetente_nome,
                'rota' => $e->origem . ' · ' . $e->destino,
                'servico' => 'Encomenda',
                'valor' => (float) ($e->valor ?? 0),
                'recebido' => (float) $e->recebido,
                'saldo' => (float) $e->saldo,
                'situacao' => $situacao($e->pagamento_rotulo),
                'classe' => $classe($e->pagamento_rotulo),
            ]);
        }

        $linhas = $linhas->sortByDesc('data')->values();

        $rotas = $linhas->groupBy('rota')->map(fn ($g, $rota) => [
            'rota' => $rota,
            'passagens' => $g->where('servico', 'Passagem')->count(),
            'encomendas' => $g->where('servico', 'Encomenda')->count(),
            'valor' => $g->sum('valor'),
            'recebido' => $g->sum('recebido'),
            'fiado' => $g->sum('saldo'),
        ])->sortByDesc('valor')->values();

        // Situação atual (não depende do período)
        $agendadas = $motorista->viagens()
            ->where('situacao', 'agendada')
            ->withCount(['passagens as ocupados' => fn ($q) => $q->where('situacao', 'ativa')])
            ->get();

        // Formas de pagamento (valores recebidos no período)
        $totais = $pagamentos()
            ->where('pg.situacao', 'pago')
            ->whereBetween('pg.data_pagamento', [$inicio, $fim])
            ->selectRaw('pg.forma, SUM(pg.valor) as total')
            ->groupBy('pg.forma')
            ->pluck('total', 'forma');

        $formas = [];
        $acumulado = 0.0;
        $trechos = [];

        foreach (self::FORMAS as $chave => [$rotulo, $cor]) {
            $total = (float) ($totais[$chave] ?? 0);

            if ($total <= 0) {
                continue;
            }

            $pct = $recebidoPeriodo > 0 ? round($total / $recebidoPeriodo * 100, 1) : 0;
            $formas[] = ['rotulo' => $rotulo, 'cor' => $cor, 'total' => $total, 'pct' => $pct];

            $trechos[] = "{$cor} {$acumulado}% " . ($acumulado + $pct) . '%';
            $acumulado += $pct;
        }

        if ($trechos) {
            $trechos[count($trechos) - 1] = preg_replace('/ [\d.]+%$/', ' 100%', end($trechos));
        }

        return [
            'recebidoMes' => $recebidoMes,
            'recebidoPeriodo' => $recebidoPeriodo,
            'fiadoTotal' => $fiadoTotal,
            'valorVendas' => (float) $linhas->sum('valor'),
            'valorMedio' => $passagens->count() ? (float) $passagens->avg('valor') : 0.0,
            'clientes' => $passagens->pluck('passageiro_id')->unique()->count(),
            'rotasCadastradas' => $agendadas->count(),
            'passagensOcupadas' => (int) $agendadas->sum('ocupados'),
            'assentosLivres' => (int) $agendadas->sum(fn ($v) => max(0, $v->capacidade - $v->ocupados)),
            'veiculosDisponiveis' => $motorista->veiculos()->where('situacao', 'disponivel')->count(),
            'linhas' => $linhas,
            'rotas' => $rotas,
            'formas' => $formas,
            'rosca' => $trechos ? 'conic-gradient(' . implode(', ', $trechos) . ')' : 'conic-gradient(#e8eef3 0 100%)',
        ] + $this->serie($pagamentos(), $inicio, $fim);
    }

    /** Recebimentos por dia (períodos curtos) ou por mês (períodos longos), para o gráfico */
    private function serie($consulta, Carbon $inicio, Carbon $fim): array
    {
        $dias = (int) $inicio->copy()->startOfDay()->diffInDays($fim->copy()->startOfDay()) + 1;
        $mensal = $dias > 45;

        $buckets = [];
        $cursor = $mensal ? $inicio->copy()->startOfMonth() : $inicio->copy()->startOfDay();

        while ($cursor->lte($fim)) {
            $buckets[$cursor->format($mensal ? 'Y-m' : 'Y-m-d')] = [
                'rotulo' => $mensal ? $cursor->translatedFormat('M/y') : $cursor->format('d/m'),
                'passagens' => 0.0,
                'encomendas' => 0.0,
            ];
            $mensal ? $cursor->addMonth() : $cursor->addDay();
        }

        $recebimentos = $consulta
            ->where('pg.situacao', 'pago')
            ->whereBetween('pg.data_pagamento', [$inicio, $fim])
            ->get(['pg.data_pagamento', 'pg.valor', 'pg.passagem_id']);

        foreach ($recebimentos as $r) {
            $chave = Carbon::parse($r->data_pagamento)->format($mensal ? 'Y-m' : 'Y-m-d');

            if (isset($buckets[$chave])) {
                $buckets[$chave][$r->passagem_id ? 'passagens' : 'encomendas'] += (float) $r->valor;
            }
        }

        $serie = array_values($buckets);
        $maior = max(array_map(fn ($b) => $b['passagens'] + $b['encomendas'], $serie) ?: [0]);

        if ($maior <= 0) {
            $topo = 100;
        } else {
            $passo = 10 ** floor(log10($maior));
            $topo = ceil($maior / $passo) * $passo;
        }

        return ['serie' => $serie, 'topo' => $topo, 'serieVazia' => $maior <= 0];
    }
}
