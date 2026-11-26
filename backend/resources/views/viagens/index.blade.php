@extends('layouts.app')

@section('titulo', 'Minhas viagens')
@section('classe', 'pagina-viagens')

@section('conteudo')
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $rotulos = ['concluida' => 'Concluída', 'cancelada' => 'Cancelada'];
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Minhas viagens</h1>
            <p class="introducao">Consulte destinos, datas, situação e disponibilidade.</p>
        </div>
        <a class="botao" href="#nova-viagem">+ Nova viagem</a>
    </div>

    @if (session('status'))
        <p class="sucesso-formulario">{{ session('status') }}</p>
    @endif
    @if (session('erro'))
        <p class="erro-formulario">{{ session('erro') }}</p>
    @endif

    <section class="cartao bloco filtros">
        <form method="GET" action="{{ route('viagens.index') }}">
            <div class="grade-formulario">
                <label>
                    Origem ou destino
                    <input type="text" name="busca" value="{{ $busca }}" placeholder="Buscar cidade">
                </label>
                <label>
                    Data
                    <input type="date" name="data" value="{{ $data }}">
                </label>
            </div>
            <button type="submit" class="botao">Buscar</button>
            @if ($busca !== '' || $data !== '')
                <a href="{{ route('viagens.index') }}">Limpar filtros</a>
            @endif
        </form>
    </section>

    <div class="grade-cartoes">
        @forelse ($viagens as $v)
            @php
                $ocupados = $v->passagens->count();
                $vagas = $v->capacidade - $ocupados;
                $agendada = $v->situacao === 'agendada';
            @endphp
            <div class="registro-viagem">
                <article class="cartao bloco viagem-card">
                    <div class="linha">
                        <span class="selo {{ $agendada ? '' : 'neutro' }}">
                            @if (! $agendada)
                                {{ $rotulos[$v->situacao] ?? $v->situacao }}
                            @elseif ($vagas <= 0)
                                Lotada
                            @elseif ($vagas === 1)
                                1 vaga disponível
                            @else
                                {{ $vagas }} vagas disponíveis
                            @endif
                        </span>
                        <small>{{ $v->saida->translatedFormat('d \d\e M') }} · {{ $v->saida->format('H:i') }}</small>
                    </div>
                    <div class="trajeto">
                        <div>
                            <h3>{{ $v->origem }}</h3>
                            <small>Saída às {{ $v->saida->format('H:i') }}</small>
                        </div>
                        <span class="icone icone-viajar-por" aria-hidden="true"></span>
                        <div>
                            <h3>{{ $v->destino }}</h3>
                        </div>
                    </div>
                    <div class="linha">
                        <small class="tipo-veiculo"><img class="icone-transporte" src="{{ asset('assets/bus.png') }}" alt="" aria-hidden="true">{{ $v->veiculo->tipo === 'van' ? 'Van' : 'Ônibus' }} · {{ $v->capacidade }} assentos</small>
                        <strong class="preco">{{ $dinheiro($v->valor) }}<small>/ pessoa</small></strong>
                    </div>
                    <div class="ocupacao">
                        <progress max="{{ $v->capacidade }}" value="{{ $ocupados }}" aria-label="{{ $ocupados }} de {{ $v->capacidade }} ocupados"></progress>
                        <small>{{ $ocupados }}/{{ $v->capacidade }} ocupados</small>
                    </div>
                    <div class="linha final-card">
                        <span></span>
                        <a href="#detalhe-viagem-{{ $v->id }}">Ver viagem</a>
                    </div>
                </article>

                @if ($agendada)
                    <div class="acoes-registro">
                        <a href="#editar-viagem-{{ $v->id }}">Editar</a>
                        <form method="POST" action="{{ route('viagens.concluir', $v->id) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="link-botao">Marcar como concluída</button>
                        </form>
                        <a class="perigo" href="#cancelar-viagem-{{ $v->id }}">Cancelar viagem</a>
                    </div>
                @endif
            </div>
        @empty
            <p class="texto-secundario">
                @if ($busca !== '' || $data !== '')
                    Nenhuma viagem encontrada com esses filtros.
                @else
                    Você ainda não cadastrou nenhuma viagem. Toque em "+ Nova viagem" para começar.
                @endif
            </p>
        @endforelse
    </div>
@endsection

@section('janelas')
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $situacaoPagamento = fn ($passagem) => $passagem->pagamentos->contains('situacao', 'pendente') ? 'Fiado' : 'Pago';
    @endphp

    @include('viagens._formulario', [
        'idPainel' => 'nova-viagem',
        'titulo' => 'Nova viagem',
        'acao' => route('viagens.store'),
        'metodo' => 'POST',
        'viagem' => null,
        'bag' => 'default',
        'usarOld' => ! session('editando'),
        'botao' => 'Salvar',
    ])

    @foreach ($viagens as $v)
        @php
            $ocupados = $v->passagens->count();
            $ocupadosAssentos = $v->passagens->pluck('assento')->all();
        @endphp

        {{-- Detalhe da viagem com o mapa de assentos --}}
        <section class="janela" id="detalhe-viagem-{{ $v->id }}" aria-label="Detalhes da viagem">
            <a class="fundo-janela" href="{{ route('viagens.index') }}" aria-label="Fechar painel"></a>

            <section class="painel" role="dialog" aria-modal="true" aria-labelledby="detalhe-viagem-{{ $v->id }}-titulo" tabindex="-1">
                <header class="painel-cabecalho">
                    <div>
                        <span class="marca-painel">ROTA FÁCIL</span>
                        <h2 id="detalhe-viagem-{{ $v->id }}-titulo">Detalhes da viagem</h2>
                    </div>
                    <a class="fechar-painel" href="{{ route('viagens.index') }}" aria-label="Fechar detalhes da viagem">×</a>
                </header>

                <h2>{{ $v->origem }} → {{ $v->destino }}</h2>
                <p class="texto-secundario">{{ $v->saida->format('d/m/Y') }} • {{ $v->saida->format('H:i') }}</p>

                <dl class="resumo-detalhes">
                    <div>
                        <dt>Veículo</dt>
                        <dd>{{ $v->veiculo->modelo }} · {{ substr($v->veiculo->placa, 0, 3) }}-{{ substr($v->veiculo->placa, 3) }}</dd>
                    </div>
                    <div>
                        <dt>Passagem</dt>
                        <dd>{{ $dinheiro($v->valor) }}</dd>
                    </div>
                    <div>
                        <dt>Disponíveis</dt>
                        <dd>{{ $v->capacidade - $ocupados }} de {{ $v->capacidade }} lugares</dd>
                    </div>
                </dl>

                <fieldset class="campo-assentos">
                    <legend class="sr-only">Mapa de assentos</legend>
                    <p class="frente">FRENTE DO VEÍCULO</p>
                    <div class="assentos">
                        @for ($n = 1; $n <= $v->capacidade; $n++)
                            <span class="assento visual {{ in_array($n, $ocupadosAssentos) ? 'ocupado' : 'livre' }}">{{ sprintf('%02d', $n) }}</span>
                        @endfor
                    </div>
                </fieldset>
                <div class="legenda">
                    <span><i class="livre"></i> Livre</span>
                    <span><i class="ocupado"></i> Ocupado</span>
                </div>

                <div class="linha">
                    <h2>Passageiros • {{ $ocupados }}</h2>
                </div>
                <div class="lista-passageiros">
                    @forelse ($v->passagens as $passagem)
                        <article class="cartao bloco">
                            <h3>{{ $passagem->passageiro->nome }}</h3>
                            <p class="texto-secundario">Assento {{ $passagem->assento }} • {{ $situacaoPagamento($passagem) }}</p>
                        </article>
                    @empty
                        <p class="texto-secundario">Nenhuma passagem vendida para esta viagem.</p>
                    @endforelse
                </div>

                <div class="acoes">
                    @if ($v->situacao === 'agendada')
                        <a class="botao secundario" href="#editar-viagem-{{ $v->id }}">Editar viagem</a>
                        @if ($ocupados < $v->capacidade)
                            <a class="botao" href="{{ route('passagens.index', ['viagem' => $v->id]) }}#vender-passagem">Vender passagem</a>
                        @endif
                    @else
                        <a class="botao secundario" href="{{ route('viagens.index') }}">Voltar</a>
                    @endif
                </div>
            </section>
        </section>

        @if ($v->situacao === 'agendada')
            @include('viagens._formulario', [
                'idPainel' => 'editar-viagem-' . $v->id,
                'titulo' => 'Editar viagem',
                'acao' => route('viagens.update', $v->id),
                'metodo' => 'PUT',
                'viagem' => $v,
                'bag' => 'viagem_' . $v->id,
                'usarOld' => session('editando') == $v->id,
                'botao' => 'Salvar alterações',
            ])

            {{-- Cancelar viagem --}}
            <section class="janela" id="cancelar-viagem-{{ $v->id }}" aria-label="Painel: cancelar viagem">
                <a class="fundo-janela" href="{{ route('viagens.index') }}" aria-label="Fechar painel"></a>

                <section class="painel" role="dialog" aria-modal="true" aria-labelledby="cancelar-viagem-{{ $v->id }}-titulo" tabindex="-1">
                    <header class="painel-cabecalho">
                        <div>
                            <span class="marca-painel">ROTA FÁCIL</span>
                            <h2 id="cancelar-viagem-{{ $v->id }}-titulo">Cancelar viagem</h2>
                        </div>
                        <a class="fechar-painel" href="{{ route('viagens.index') }}" aria-label="Fechar cancelar viagem">×</a>
                    </header>

                    <form class="formulario" method="POST" action="{{ route('viagens.cancelar', $v->id) }}">
                        @csrf
                        <div class="grade-formulario">
                            <p class="nota largura-total">
                                {{ $v->origem }} → {{ $v->destino }} • {{ $v->saida->format('d/m/Y • H:i') }}<br>
                                @if ($ocupados > 0)
                                    Esta viagem tem {{ $ocupados }} passagem(ns) ativa(s). Elas serão canceladas e os assentos liberados. Devolução de valores já recebidos deve ser combinada com o passageiro, fora do sistema.
                                @else
                                    Esta viagem não tem passagens vendidas.
                                @endif
                            </p>
                            <label class="largura-total">
                                Motivo do cancelamento
                                <small>opcional</small>
                                <input type="text" name="motivo" maxlength="150">
                            </label>
                        </div>
                        <div class="acoes">
                            <a class="botao secundario" href="{{ route('viagens.index') }}">Voltar</a>
                            <button type="submit" class="botao">Confirmar cancelamento</button>
                        </div>
                    </form>
                </section>
            </section>
        @endif
    @endforeach
@endsection
