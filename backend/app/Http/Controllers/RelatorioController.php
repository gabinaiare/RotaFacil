<?php

namespace App\Http\Controllers;

use App\Models\Pagamento;
use App\Models\Passagem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RelatorioController extends Controller
{
    private const PERIODOS = ['7d', '30d', '6m', '1a', 'custom'];

    public function index(Request $request)
    {
        [$periodo, $inicio, $fim, $erroPeriodo] = $this->periodo($request);
        $m = $request->user();
        $busca = trim((string) $request->query('busca'));

        $passagens = $this->passagensDoPeriodo($m, $inicio, $fim);
        $encomendas = $this->encomendasDoPeriodo($m, $inicio, $fim);

        $linhas = $this->linhas($passagens, $encomendas);
        $linhasFiltradas = $busca === ''
            ? $linhas
            : $linhas->filter(fn ($l) => mb_stripos($l['codigo'] . ' ' . $l['cliente'] . ' ' . $l['rota'], $busca) !== false)->values();

        // Recebimentos do período (pela data do pagamento)
        $passagemIds = Passagem::whereHas('viagem', fn ($q) => $q->where('user_id', $m->id))->pluck('id');
        $encomendaIds = $m->encomendas()->pluck('id');

        $recebidos = Pagamento::where('situacao', 'pago')
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->where(fn ($q) => $q->whereIn('passagem_id', $passagemIds)->orWhereIn('encomenda_id', $encomendaIds))
            ->get();

        $inicioMes = now()->startOfMonth();
        $recebidoMes = (float) Pagamento::where('situacao', 'pago')
            ->whereBetween('data_pagamento', [$inicioMes, now()->endOfDay()])
            ->where(fn ($q) => $q->whereIn('passagem_id', $passagemIds)->orWhereIn('encomenda_id', $encomendaIds))
            ->sum('valor');

        // Fiado atual (não depende do período): passagens ativas e encomendas
        $ativasIds = Passagem::whereHas('viagem', fn ($q) => $q->where('user_id', $m->id))
            ->where('situacao', 'ativa')->pluck('id');

        $fiado = (float) Pagamento::where('situacao', 'pendente')
            ->where(fn ($q) => $q->whereIn('passagem_id', $ativasIds)->orWhereIn('encomenda_id', $encomendaIds))
            ->sum('valor');

        // Números gerais das viagens programadas
        $agendadas = $m->viagens()->where('situacao', 'agendada')->get();
        $ocupadas = Passagem::whereIn('viagem_id', $agendadas->pluck('id'))->where('situacao', 'ativa')->count();
        $lugares = (int) $agendadas->sum('capacidade');

        $qtd = $passagens->count();
        $valorVendas = (float) $passagens->sum('valor');

        return view('relatorios.index', [
            'periodo' => $periodo,
            'inicio' => $inicio,
            'fim' => $fim,
            'erroPeriodo' => $erroPeriodo,
            'busca' => $busca,
            'recebidoPeriodo' => round((float) $recebidos->sum('valor'), 2),
            'recebidoMes' => round($recebidoMes, 2),
            'valorVendas' => round($valorVendas + (float) $encomendas->sum('valor'), 2),
            'valorMedio' => $qtd > 0 ? round($valorVendas / $qtd, 2) : 0,
            'clientes' => $passagens->pluck('passageiro_id')->unique()->count(),
            'fiado' => round($fiado, 2),
            'rotasCadastradas' => $m->viagens()->count(),
            'passagensOcupadas' => $ocupadas,
            'assentosLivres' => max(0, $lugares - $ocupadas),
            'veiculos' => $m->veiculos()->count(),
            'serie' => $this->serie($recebidos, $inicio, $fim),
            'formas' => $this->formas($recebidos),
            'rotas' => $this->rotas($passagens, $encomendas),
            'linhas' => $linhasFiltradas,
            'totalLinhas' => $linhas->count(),
        ]);
    }

    public function csv(Request $request)
    {
        [, $inicio, $fim] = $this->periodo($request);
        $m = $request->user();

        $linhas = $this->linhas(
            $this->passagensDoPeriodo($m, $inicio, $fim),
            $this->encomendasDoPeriodo($m, $inicio, $fim)
        );

        $nome = 'relatorio-vendas-' . $inicio->format('Ymd') . '-' . $fim->format('Ymd') . '.csv';

        return response()->streamDownload(function () use ($linhas) {
            $f = fopen('php://output', 'w');
            fwrite($f, "\xEF\xBB\xBF"); // para o Excel abrir os acentos
            fputcsv($f, ['Código', 'Passageiro / remetente', 'Rota', 'Serviço', 'Valor', 'Situação', 'Data'], ';');

            foreach ($linhas as $l) {
                fputcsv($f, [
                    $l['codigo'], $l['cliente'], $l['rota'], $l['servico'],
                    number_format($l['valor'], 2, ',', ''), $l['situacao'], $l['data']->format('d/m/Y'),
                ], ';');
            }

            fclose($f);
        }, $nome, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** @return array{0:string,1:Carbon,2:Carbon,3:?string} */
    private function periodo(Request $request): array
    {
        $periodo = $request->query('periodo', '30d');
        if (! in_array($periodo, self::PERIODOS, true)) {
            $periodo = '30d';
        }

        $fim = now()->endOfDay();
        $inicio = match ($periodo) {
            '7d' => now()->subDays(6)->startOfDay(),
            '6m' => now()->subMonths(6)->startOfDay(),
            '1a' => now()->subYear()->startOfDay(),
            default => now()->subDays(29)->startOfDay(),
        };
        $erro = null;

        if ($periodo === 'custom' && $request->query('inicio') && $request->query('fim')) {
            $v = Validator::make($request->query(), [
                'inicio' => ['date'],
                'fim' => ['date', 'after_or_equal:inicio'],
            ]);

            if ($v->passes()) {
                $inicio = Carbon::parse($request->query('inicio'))->startOfDay();
                $fim = Carbon::parse($request->query('fim'))->endOfDay();
            } else {
                $erro = 'Período inválido: a data final deve ser igual ou posterior à inicial.';
            }
        }

        return [$periodo, $inicio, $fim, $erro];
    }

    private function passagensDoPeriodo($m, Carbon $inicio, Carbon $fim)
    {
        return Passagem::whereHas('viagem', fn ($q) => $q->where('user_id', $m->id))
            ->where('situacao', 'ativa')
            ->whereBetween('created_at', [$inicio, $fim])
            ->with(['viagem', 'passageiro', 'pagamentos'])
            ->get();
    }

    private function encomendasDoPeriodo($m, Carbon $inicio, Carbon $fim)
    {
        return $m->encomendas()->whereBetween('created_at', [$inicio, $fim])->with('pagamentos')->get();
    }

    private function linhas($passagens, $encomendas)
    {
        $p = $passagens->map(fn ($x) => [
            'codigo' => $x->codigo,
            'cliente' => $x->passageiro->nome,
            'rota' => $x->viagem->origem . ' · ' . $x->viagem->destino,
            'servico' => 'Passagem',
            'valor' => (float) $x->valor,
            'situacao' => $x->situacao_pagamento,
            'classe' => $x->saldo <= 0 ? 'verde' : ($x->recebido > 0 ? 'ciano' : 'amarelo'),
            'data' => $x->created_at,
        ]);

        $e = $encomendas->map(fn ($x) => [
            'codigo' => $x->codigo,
            'cliente' => $x->remetente_nome,
            'rota' => $x->origem . ' · ' . $x->destino,
            'servico' => 'Encomenda',
            'valor' => (float) $x->valor,
            'situacao' => $x->pagamento_rotulo,
            'classe' => (float) $x->valor <= 0 ? '' : ($x->saldo <= 0 ? 'verde' : ($x->recebido > 0 ? 'ciano' : 'amarelo')),
            'data' => $x->created_at,
        ]);

        return $p->concat($e)->sortByDesc('data')->values();
    }

    /** Recebimentos agrupados por dia (ou por mês, se o período for longo) */
    private function serie($recebidos, Carbon $inicio, Carbon $fim): array
    {
        $porMes = $inicio->diffInDays($fim) > 62;
        $formato = $porMes ? 'm/Y' : 'd/m';

        $grupos = [];
        for ($d = $inicio->copy(); $d->lte($fim); $porMes ? $d->addMonth()->startOfMonth() : $d->addDay()) {
            $grupos[$d->format($formato)] = ['rotulo' => $d->format($formato), 'passagens' => 0.0, 'encomendas' => 0.0];
        }

        foreach ($recebidos as $r) {
            $chave = Carbon::parse($r->data_pagamento)->format($formato);
            if (isset($grupos[$chave])) {
                $grupos[$chave][$r->passagem_id ? 'passagens' : 'encomendas'] += (float) $r->valor;
            }
        }

        return array_values($grupos);
    }

    private function formas($recebidos): array
    {
        $total = (float) $recebidos->sum('valor');
        $nomes = ['pix' => 'Pix', 'dinheiro' => 'Dinheiro', 'cartao' => 'Cartão'];

        return $recebidos->groupBy('forma')->map(function ($g, $forma) use ($total, $nomes) {
            $v = round((float) $g->sum('valor'), 2);

            return ['nome' => $nomes[$forma] ?? 'Não informado', 'valor' => $v, 'pct' => $total > 0 ? round($v / $total * 100, 1) : 0];
        })->sortByDesc('valor')->values()->all();
    }

    private function rotas($passagens, $encomendas): array
    {
        $rotas = [];

        foreach ($passagens as $p) {
            $k = $p->viagem->origem . ' · ' . $p->viagem->destino;
            $rotas[$k] ??= ['rota' => $k, 'passagens' => 0, 'encomendas' => 0, 'vendido' => 0.0, 'recebido' => 0.0, 'fiado' => 0.0];
            $rotas[$k]['passagens']++;
            $rotas[$k]['vendido'] += (float) $p->valor;
            $rotas[$k]['recebido'] += $p->recebido;
            $rotas[$k]['fiado'] += $p->saldo;
        }

        foreach ($encomendas as $e) {
            $k = $e->origem . ' · ' . $e->destino;
            $rotas[$k] ??= ['rota' => $k, 'passagens' => 0, 'encomendas' => 0, 'vendido' => 0.0, 'recebido' => 0.0, 'fiado' => 0.0];
            $rotas[$k]['encomendas']++;
            $rotas[$k]['vendido'] += (float) $e->valor;
            $rotas[$k]['recebido'] += $e->recebido;
            $rotas[$k]['fiado'] += $e->saldo;
        }

        return collect($rotas)->sortByDesc('vendido')->values()->all();
    }
}
