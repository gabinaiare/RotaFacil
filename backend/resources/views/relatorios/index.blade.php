@extends('layouts.app')

@section('titulo', 'Relatórios')
@section('classe', 'pagina-relatorios')

@section('conteudo')
    <link rel="stylesheet" href="{{ asset('css/impressao.css') }}">

    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $nomeMes = mb_strtoupper(now()->translatedFormat('F'));
        $consulta = request()->only('periodo', 'de', 'ate');
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

    <form method="GET" action="{{ route('relatorios.index') }}">
        <div class="seletor-periodo" role="group" aria-label="Período">
            @foreach (['7d' => '7 dias', '30d' => '30 dias', '6m' => '6 meses', '1a' => '1 ano', 'custom' => 'Personalizado'] as $valor => $rotulo)
                <label>
                    <input type="radio" name="periodo" value="{{ $valor }}" @checked($periodo === $valor) onchange="this.form.submit()">
                    <span>{{ $rotulo }}</span>
                </label>
            @endforeach
        </div>
    </form>

    @if ($periodo === 'custom')
        <section class="periodo-personalizado cartao" aria-label="Período personalizado">
            <form method="GET" action="{{ route('relatorios.index') }}">
                <input type="hidden" name="periodo" value="custom">
                <label>
                    De
                    <input type="date" name="de" value="{{ $inicio->format('Y-m-d') }}">
                </label>
                <label>
                    Até
                    <input type="date" name="ate" value="{{ $fim->format('Y-m-d') }}">
                </label>
                <button class="botao" type="submit">Aplicar período</button>
            </form>
        </section>
    @endif

    <section class="destaque-mes">
        <span>MÊS ATUAL · {{ $nomeMes }}</span>
        <h2>{{ $dinheiro($recebidoMes) }}</h2>
        <p>Recebido de {{ now()->startOfMonth()->format('d/m/Y') }} até {{ now()->format('d/m/Y') }}</p>
        <div>
            <article>
                <small>Valor das vendas no período</small>
                <strong>{{ $dinheiro($valorVendas) }}</strong>
            </article>

            <article>
                <small>Valor médio da passagem</small>
                <strong>{{ $dinheiro($valorMedio) }}</strong>
            </article>

            <article>
                <small>Clientes no período</small>
                <strong>{{ $clientes }}</strong>
            </article>
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
            <strong class="amarelo">{{ $dinheiro($fiadoTotal) }}</strong>
            <a class="link-fiado" href="{{ route('passageiros.index', ['filtro' => 'fiado']) }}">Ver clientes com fiado</a>
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
            <strong>{{ $veiculosDisponiveis }}</strong>
            <small>Prontos para viajar</small>
        </article>
    </section>

    <div class="grade-graficos">
        <section class="cartao painel-estatistica">
            <header>
                <h2>Recebimentos ao longo do tempo</h2>
                <p>Passagens e encomendas, pela data do pagamento.</p>
            </header>

            <div class="conteudo-grafico">
                <div class="linha">
                    <div class="legenda-grafico">
                        <span><i style="background:#18aa80"></i> Passagens</span>
                        <span><i style="background:#2f80ed"></i> Encomendas</span>
                    </div>
                    <small>Valores em R$</small>
                </div>

                @if ($serieVazia)
                    <p class="texto-secundario">Nenhum recebimento neste período.</p>
                @else
                    @php
                        $n = count($serie);
                        $largura = max(540, 58 + $n * 24);
                        $passoX = ($largura - 58) / $n;
                        $barra = round(min(16, max(4, $passoX * 0.6)), 2);
                        $cada = max(1, (int) ceil($n / 12));
                        $casas = $topo < 40 ? 1 : 0;
                    @endphp
                    <div class="rolagem-grafico" tabindex="0" role="region" aria-label="Recebimentos por data. Role para os lados para ver todas as datas.">
                        <svg class="grafico-dinamico" viewBox="0 0 {{ $largura }} 220" width="{{ $largura }}" height="220" role="img" aria-label="Gráfico de recebimentos no período">
                            @for ($i = 0; $i <= 4; $i++)
                                @php $y = 180 - $i * 40; @endphp
                                <line x1="38" y1="{{ $y }}" x2="{{ $largura - 20 }}" y2="{{ $y }}" stroke="#e8eef3"></line>
                                <text x="30" y="{{ $y + 4 }}" text-anchor="end">{{ number_format($topo * $i / 4, $casas, ',', '.') }}</text>
                            @endfor

                            @foreach ($serie as $i => $b)
                                @php
                                    $x = round(38 + $passoX * ($i + 0.5) - $barra / 2, 2);
                                    $hp = round($b['passagens'] / $topo * 160, 2);
                                    $he = round($b['encomendas'] / $topo * 160, 2);
                                @endphp
                                @if ($hp > 0)
                                    <rect x="{{ $x }}" y="{{ 180 - $hp }}" width="{{ $barra }}" height="{{ $hp }}" rx="2" fill="#18aa80"></rect>
                                @endif
                                @if ($he > 0)
                                    <rect x="{{ $x }}" y="{{ 180 - $hp - $he }}" width="{{ $barra }}" height="{{ $he }}" rx="2" fill="#2f80ed"></rect>
                                @endif
                                @if ($i % $cada === 0)
                                    <text x="{{ round($x + $barra / 2, 2) }}" y="205" text-anchor="middle">{{ $b['rotulo'] }}</text>
                                @endif
                            @endforeach
                        </svg>
                    </div>
                    <small class="dica-grafico">Deslize para os lados para ver todas as datas.</small>
                @endif
            </div>
        </section>

        <section class="cartao painel-estatistica">
            <header>
                <h2>Formas de pagamento</h2>
            </header>

            @if (count($formas))
                <div class="rosca-dinamica" style="background: {{ $rosca }}" role="img" aria-label="Recebido no período: {{ $dinheiro($recebidoPeriodo) }}">
                    <div>
                        <small>Recebido</small>
                        <strong>{{ $dinheiro($recebidoPeriodo) }}</strong>
                    </div>
                </div>

                <ul class="lista-pagamentos">
                    @foreach ($formas as $f)
                        <li>
                            <span><i style="background: {{ $f['cor'] }}"></i> {{ $f['rotulo'] }}</span>
                            <strong>{{ $dinheiro($f['total']) }}</strong>
                            <small>{{ number_format($f['pct'], 1, ',', '.') }}%</small>
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="texto-secundario">Nenhum recebimento neste período.</p>
            @endif
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
                            <td data-label="Valor vendido">{{ $dinheiro($r['valor']) }}</td>
                            <td data-label="Já recebido destas vendas"><strong class="verde">{{ $dinheiro($r['recebido']) }}</strong></td>
                            <td data-label="Fiado atual"><span class="amarelo">{{ $dinheiro($r['fiado']) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Nenhuma venda neste período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="cabecalho-resultados">
        <h2>Vendas e encomendas</h2>
        <small>{{ $linhas->count() }} {{ $linhas->count() === 1 ? 'registro' : 'registros' }} no período</small>
    </div>

    <section class="cartao vendas-estatistica">
        <header class="linha">
            <div>
                <label class="sr-only" for="busca-vendas">Buscar cliente ou rota</label>
                <input id="busca-vendas" type="search" placeholder="Buscar cliente, código ou rota">
                <p class="texto-secundario">Recebido e fiado mostram o saldo atual de cada venda.</p>
            </div>

            <div class="acoes-exportar">
                <a class="botao secundario" href="{{ route('relatorios.exportar', $consulta) }}">Exportar CSV</a>
                <a class="botao" href="#" onclick="window.print(); return false;">Imprimir / salvar PDF</a>
            </div>
        </header>

        <div class="tabela-rolagem">
            <table id="tabela-vendas">
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
                        <tr data-busca="{{ mb_strtolower($l['codigo'] . ' ' . $l['nome'] . ' ' . $l['rota'] . ' ' . $l['servico']) }}">
                            <td data-label="Código">{{ $l['codigo'] }}</td>
                            <td data-label="Passageiro / remetente">{{ $l['nome'] }}</td>
                            <td data-label="Rota"><strong>{{ $l['rota'] }}</strong></td>
                            <td data-label="Serviço">{{ $l['servico'] }}</td>
                            <td data-label="Valor"><strong class="{{ $l['classe'] }}">{{ $dinheiro($l['valor']) }}</strong></td>
                            <td data-label="Situação">{{ $l['situacao'] }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">Nenhuma venda ou encomenda neste período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <script>
        document.getElementById('busca-vendas').addEventListener('input', function () {
            var termo = this.value.trim().toLowerCase();
            document.querySelectorAll('#tabela-vendas tbody tr[data-busca]').forEach(function (linha) {
                linha.hidden = termo !== '' && linha.dataset.busca.indexOf(termo) === -1;
            });
        });
    </script>
@endsection
