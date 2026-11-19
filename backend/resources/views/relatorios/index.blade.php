@extends('layouts.app')

@section('titulo', 'Relatórios')
@section('classe', 'pagina-relatorios')

@section('conteudo')
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $cores = ['#18aa80', '#2f80c8', '#e0a82e', '#8a6cd9'];
        $opcoes = ['7d' => '7 dias', '30d' => '30 dias', '6m' => '6 meses', '1a' => '1 ano', 'custom' => 'Personalizado'];
        $nomeMes = mb_strtoupper(now()->translatedFormat('F'));
    @endphp

    <div class="marca-relatorio-impresso">
        <strong>Rota Fácil</strong>
        <span>{{ auth()->user()->name }}</span>
    </div>

    <div class="titulo-pagina">
        <div>
            <h1>Relatórios</h1>
            <p class="introducao">Dados das suas viagens, passagens, pagamentos e encomendas.</p>
        </div>
    </div>

    @if ($erroPeriodo)
        <p class="erro-formulario">{{ $erroPeriodo }}</p>
    @endif

    <form method="GET" action="{{ route('relatorios.index') }}">
        <div class="seletor-periodo" role="group" aria-label="Período">
            @foreach ($opcoes as $chave => $rotulo)
                <label>
                    <input type="radio" name="periodo" value="{{ $chave }}" @checked($periodo === $chave) onchange="this.form.submit()">
                    <span>{{ $rotulo }}</span>
                </label>
            @endforeach
        </div>
        <noscript><button class="botao" type="submit">Aplicar</button></noscript>
    </form>

    <section class="periodo-personalizado cartao" aria-label="Período personalizado">
        <form method="GET" action="{{ route('relatorios.index') }}">
            <input type="hidden" name="periodo" value="custom">
            <label>De <input type="date" name="inicio" value="{{ $inicio->format('Y-m-d') }}"></label>
            <label>Até <input type="date" name="fim" value="{{ $fim->format('Y-m-d') }}"></label>
            <button class="botao" type="submit">Aplicar período</button>
        </form>
    </section>

    <section class="destaque-mes">
        <span>MÊS ATUAL · {{ $nomeMes }}</span>
        <h2>{{ $dinheiro($recebidoMes) }}</h2>
        <p>Recebido de {{ now()->startOfMonth()->format('d/m/Y') }} até {{ now()->format('d/m/Y') }}</p>
        <div>
            <article><small>Valor das vendas no período</small><strong>{{ $dinheiro($valorVendas) }}</strong></article>
            <article><small>Valor médio da passagem</small><strong>{{ $dinheiro($valorMedio) }}</strong></article>
            <article><small>Clientes no período</small><strong>{{ $clientes }}</strong></article>
        </div>
    </section>

    <div class="periodo-resumo">
        <span>{{ $inicio->format('d/m/Y') }} a {{ $fim->format('d/m/Y') }}</span>
    </div>

    <section class="indicadores">
        <article class="indicador">
            <p>Recebido no período</p>
            <strong class="verde">{{ $dinheiro($recebidoPeriodo) }}</strong>
            <small>Pagamentos registrados</small>
        </article>
        <article class="indicador">
            <p>Fiado a receber</p>
            <strong class="amarelo">{{ $dinheiro($fiado) }}</strong>
            <a class="link-fiado" href="{{ route('passageiros.index') }}#clientes-com-fiado">Ver clientes com fiado</a>
        </article>
        <article class="indicador">
            <p>Rotas cadastradas</p>
            <strong>{{ $rotasCadastradas }}</strong>
            <small>Disponíveis no seu painel</small>
        </article>
        <article class="indicador">
            <p>Passagens ocupadas</p>
            <strong>{{ $passagensOcupadas }}</strong>
            <small>Nas viagens programadas</small>
        </article>
        <article class="indicador">
            <p>Assentos disponíveis</p>
            <strong>{{ $assentosLivres }}</strong>
            <small>Somando todas as viagens</small>
        </article>
        <article class="indicador">
            <p>Meus veículos</p>
            <strong>{{ $veiculos }}</strong>
            <small>Prontos para viajar</small>
        </article>
    </section>

    <div class="grade-graficos">
        <section class="cartao painel-estatistica">
            <header>
                <h2>Recebimentos ao longo do tempo</h2>
                <p>Passagens e encomendas, pela data do pagamento.</p>
            </header>

            @php
                $n = count($serie);
                $maximo = max(1, collect($serie)->map(fn ($s) => max($s['passagens'], $s['encomendas']))->max());
                $passo = max(1, (int) ceil($n / 10));
                $largura = max(540, $n * 36 + 50);
                $larguraGrupo = ($largura - 50) / max(1, $n);
            @endphp

            <div class="conteudo-grafico">
                <div class="linha">
                    <div class="legenda-grafico">
                        <span><i></i> Passagens</span>
                        <span><i class="azul"></i> Encomendas</span>
                    </div>
                    <small>Valores em R$</small>
                </div>

                <div class="rolagem-grafico" tabindex="0" role="region" aria-label="Recebimentos por data">
                    <svg class="grafico-recebimentos" viewBox="0 0 {{ $largura }} 220" style="min-width:{{ $largura }}px" role="img" aria-label="Recebimentos por data">
                        @foreach ([0, 0.25, 0.5, 0.75, 1] as $f)
                            @php $y = 180 - $f * 160; @endphp
                            <line x1="38" y1="{{ $y }}" x2="{{ $largura - 10 }}" y2="{{ $y }}" stroke="#e8eef3"></line>
                            <text x="28" y="{{ $y + 4 }}" text-anchor="end">{{ number_format($maximo * $f, 0, ',', '.') }}</text>
                        @endforeach

                        @foreach ($serie as $i => $s)
                            @php
                                $x = 44 + $i * $larguraGrupo;
                                $hp = $s['passagens'] / $maximo * 160;
                                $he = $s['encomendas'] / $maximo * 160;
                            @endphp
                            @if ($s['passagens'] > 0)
                                <rect x="{{ $x }}" y="{{ 180 - $hp }}" width="10" height="{{ $hp }}" rx="2" fill="#18aa80"><title>Passagens {{ $s['rotulo'] }}: {{ $dinheiro($s['passagens']) }}</title></rect>
                            @endif
                            @if ($s['encomendas'] > 0)
                                <rect x="{{ $x + 12 }}" y="{{ 180 - $he }}" width="10" height="{{ $he }}" rx="2" fill="#2f80c8"><title>Encomendas {{ $s['rotulo'] }}: {{ $dinheiro($s['encomendas']) }}</title></rect>
                            @endif
                            @if ($i % $passo === 0)
                                <text x="{{ $x + 11 }}" y="207" text-anchor="middle">{{ $s['rotulo'] }}</text>
                            @endif
                        @endforeach
                    </svg>
                </div>
                <small class="dica-grafico">Deslize para os lados para ver todas as datas.</small>
            </div>
        </section>

        <section class="cartao painel-estatistica">
            <header><h2>Formas de pagamento</h2></header>

            @php
                $acumulado = 0;
                $partes = [];
                foreach ($formas as $i => $f) {
                    $ini = $acumulado;
                    $acumulado += $f['pct'];
                    $partes[] = $cores[$i % 4] . " {$ini}% {$acumulado}%";
                }
                $fundo = $partes ? 'conic-gradient(' . implode(',', $partes) . ')' : 'conic-gradient(#e8eef3 0 100%)';
            @endphp

            <div class="rosca-pagamento" role="img" aria-label="Recebido no período: {{ $dinheiro($recebidoPeriodo) }}" style="background: {{ $fundo }}">
                <div>
                    <small>Recebido</small>
                    <strong>{{ $dinheiro($recebidoPeriodo) }}</strong>
                </div>
            </div>

            <ul class="lista-pagamentos">
                @forelse ($formas as $i => $f)
                    <li><span><i style="background: {{ $cores[$i % 4] }}"></i> {{ $f['nome'] }}</span><strong>{{ $dinheiro($f['valor']) }}</strong><small>{{ number_format($f['pct'], 1, ',', '.') }}%</small></li>
                @empty
                    <li><span>Nenhum pagamento no período.</span></li>
                @endforelse
            </ul>
        </section>
    </div>

    <div class="cabecalho-resultados">
        <h2>Resultados por rota</h2>
        <small>Vendas realizadas no período</small>
    </div>

    <section class="cartao bloco">
        <div class="tabela-rolagem">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Rota</th>
                        <th scope="col">Passagens</th>
                        <th scope="col">Encomendas</th>
                        <th scope="col">Valor vendido</th>
                        <th scope="col">Já recebido destas vendas</th>
                        <th scope="col">Fiado atual</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rotas as $r)
                        <tr>
                            <td data-label="Rota"><strong>{{ $r['rota'] }}</strong></td>
                            <td data-label="Passagens">{{ $r['passagens'] }}</td>
                            <td data-label="Encomendas">{{ $r['encomendas'] }}</td>
                            <td data-label="Valor vendido">{{ $dinheiro($r['vendido']) }}</td>
                            <td data-label="Já recebido destas vendas"><strong class="verde">{{ $dinheiro($r['recebido']) }}</strong></td>
                            <td data-label="Fiado atual"><span class="amarelo">{{ $dinheiro($r['fiado']) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Não foram encontrados registros neste período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="cartao vendas-estatistica">
        <header class="linha">
            <header>
                <form method="GET" action="{{ route('relatorios.index') }}">
                    <input type="hidden" name="periodo" value="{{ $periodo }}">
                    @if ($periodo === 'custom')
                        <input type="hidden" name="inicio" value="{{ $inicio->format('Y-m-d') }}">
                        <input type="hidden" name="fim" value="{{ $fim->format('Y-m-d') }}">
                    @endif
                    <label class="sr-only" for="busca-vendas">Buscar cliente ou rota</label>
                    <input id="busca-vendas" type="search" name="busca" value="{{ $busca }}" placeholder="Buscar código, cliente ou rota">
                </form>
                <p class="texto-secundario">Pressione Enter para buscar. Recebido e fiado mostram o saldo atual de cada venda.</p>
            </header>

            <div class="acoes-exportar">
                <a class="botao secundario" href="{{ route('relatorios.csv', request()->only('periodo', 'inicio', 'fim')) }}">Exportar CSV</a>
                <a class="botao" href="#" onclick="window.print(); return false;">Imprimir / salvar PDF</a>
            </div>

            <div class="cabecalho-resultados">
                <h2>Vendas e encomendas</h2>
                <small>{{ $totalLinhas }} {{ $totalLinhas === 1 ? 'registro' : 'registros' }} no período</small>
            </div>
        </header>

        <div class="tabela-rolagem">
            <table>
                <thead>
                    <tr>
                        <th scope="col">Código</th>
                        <th scope="col">Passageiro / remetente</th>
                        <th scope="col">Rota</th>
                        <th scope="col">Serviço</th>
                        <th scope="col">Valor</th>
                        <th scope="col">Situação</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($linhas as $l)
                        <tr>
                            <td data-label="Código">{{ $l['codigo'] }}</td>
                            <td data-label="Passageiro / remetente">{{ $l['cliente'] }}</td>
                            <td data-label="Rota"><strong>{{ $l['rota'] }}</strong></td>
                            <td data-label="Serviço">{{ $l['servico'] }}</td>
                            <td data-label="Valor"><strong class="{{ $l['classe'] }}">{{ $dinheiro($l['valor']) }}</strong></td>
                            <td data-label="Situação">{{ $l['situacao'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Não foram encontrados registros para estes filtros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
