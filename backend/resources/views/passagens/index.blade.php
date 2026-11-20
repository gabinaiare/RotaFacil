@extends('layouts.app')

@section('titulo', 'Passagens')
@section('classe', 'pagina-passagens')

@section('conteudo')
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Quem vai com você?</h1>
            <p class="introducao">Venda passagens, receba pagamentos e organize os assentos da viagem.</p>
        </div>
        @if ($viagem && count($ocupados) < $viagem->capacidade)
            <a class="botao" href="#vender-passagem">+ Vender passagem</a>
        @endif
    </div>

    @if (session('status'))
        <p class="sucesso-formulario">
            {{ session('status') }}
            @if (session('comprovante'))
                <a href="{{ route('passagens.comprovante', session('comprovante')) }}" target="_blank">Ver comprovante</a>
            @endif
        </p>
    @endif
    @if (session('erro'))
        <p class="erro-formulario">{{ session('erro') }}</p>
    @endif

    @if (! $viagem)
        <p class="texto-secundario">Você não tem viagens agendadas. Cadastre uma em <a href="{{ route('viagens.index') }}#nova-viagem">Minhas viagens</a> para vender passagens.</p>
    @else
        <form method="GET" action="{{ route('passagens.index') }}">
            <label class="escolha-viagem">
                Viagem
                <select name="viagem" onchange="this.form.submit()">
                    @foreach ($viagens as $v)
                        <option value="{{ $v->id }}" @selected($v->id === $viagem->id)>{{ $v->origem }} → {{ $v->destino }} · {{ $v->saida->format('d/m/Y') }} · {{ $v->saida->format('H:i') }}</option>
                    @endforeach
                </select>
            </label>
            <noscript><button type="submit" class="botao">Ver viagem</button></noscript>
        </form>

        <section class="cartao resumo-confirmacao">
            <span class="codigo-viagem">VIAGEM {{ $viagem->codigo }}</span>
            <div class="trajeto">
                <h2>{{ $viagem->origem }}</h2>
                <span class="icone icone-viajar-por" aria-hidden="true"></span>
                <h2>{{ $viagem->destino }}</h2>
            </div>
            <div class="informacoes-confirmacao">
                <span>{{ $viagem->saida->translatedFormat('d M Y') }}</span>
                <span>Saída às {{ $viagem->saida->format('H:i') }}</span>
                <span class="tipo-veiculo"><img class="icone-transporte" src="{{ asset('assets/bus.png') }}" alt="">{{ $viagem->veiculo->modelo }} · {{ $viagem->capacidade }} assentos</span>
            </div>
            <div class="rodape-confirmacao">
                <span class="estado-presenca confirmado">{{ $dinheiro($viagem->valor) }} por pessoa</span>
                <small>{{ count($ocupados) }} de {{ $viagem->capacidade }} assentos ocupados · {{ $viagem->capacidade - count($ocupados) }} livres</small>
            </div>
        </section>

        <section class="contadores-confirmacao" aria-label="Resumo das passagens">
            <article class="cartao contador-confirmacao"><strong>{{ $contadores['ativas'] }}</strong><small>Passagens ativas</small></article>
            <article class="cartao contador-confirmacao confirmado"><strong>{{ $contadores['pagas'] }}</strong><small>Pagas</small></article>
            <article class="cartao contador-confirmacao aguardando"><strong>{{ $contadores['a_receber'] }}</strong><small>A receber</small></article>
            <article class="cartao contador-confirmacao cancelado"><strong>{{ $contadores['canceladas'] }}</strong><small>Canceladas</small></article>
        </section>

        <section class="cartao lista-confirmacoes">
            <header>
                <h2>Lista de passageiros</h2>
                <p>Passagens vendidas para esta viagem.</p>
            </header>

            @forelse ($passagens as $p)
                @php
                    $ativa = $p->situacao === 'ativa';
                    $classe = ! $ativa ? 'cancelado' : ($p->saldo > 0 ? 'aguardando' : 'confirmado');
                    $rotulo = ! $ativa ? 'Cancelada' : $p->situacao_pagamento;
                @endphp
                <article class="registro-presenca">
                    <span class="avatar">{{ $p->passageiro->iniciais }}</span>
                    <div class="dados-presenca">
                        <div class="topo-presenca">
                            <h3>{{ $p->passageiro->nome }}</h3>
                            <span class="estado-presenca {{ $classe }}">{{ $rotulo }}</span>
                        </div>
                        <p>
                            Assento {{ sprintf('%02d', $p->assento) }} · {{ $dinheiro($p->valor) }}
                            @if ($ativa && $p->saldo > 0)
                                · falta {{ $dinheiro($p->saldo) }}
                            @endif
                        </p>
                     <div class="botoes-presenca">
                            @if ($ativa)
                         @php
                        $fone = preg_replace('/\D/', '', (string) $p->passageiro->telefone);
                        $mensagem = 'Olá, ' . $p->passageiro->nome . '! Sua passagem '
                     . $viagem->origem . ' → ' . $viagem->destino
                     . ', dia ' . $viagem->saida->format('d/m')
                     . ' às ' . $viagem->saida->format('H:i')
                    . ', assento ' . sprintf('%02d', $p->assento)
                    . ', está registrada. Pode confirmar sua presença?';
                    $texto = rawurlencode($mensagem);
                    @endphp

                            <a class="botao secundario" href="{{ route('passagens.comprovante', $p->id) }}" target="_blank">Ver comprovante</a>

                                @if (strlen($fone) >= 10)
                                    <a class="botao secundario" href="https://wa.me/55{{ $fone }}?text={{ $texto }}" target="_blank" rel="noopener">Confirmar via WhatsApp</a>
                                @endif

                                @if ($p->saldo > 0)
                                    <a class="botao secundario" href="#pagamento-{{ $p->id }}">Receber</a>
                                @endif

                                <a class="botao secundario" href="#cancelar-passagem-{{ $p->id }}">Cancelar passagem</a>
                            @else
                                <span class="assento-liberado">Assento liberado</span>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <p class="texto-secundario">Nenhuma passagem vendida para esta viagem ainda.</p>
            @endforelse
        </section>
    @endif
@endsection

@section('janelas')
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
    @endphp

    @if ($viagem)
        {{-- Registrar venda de passagem (UC-08) --}}
        <section class="janela" id="vender-passagem" aria-label="Painel: vender passagem">
            <a class="fundo-janela" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}" aria-label="Fechar painel"></a>

            <section class="painel" role="dialog" aria-modal="true" aria-labelledby="vender-passagem-titulo" tabindex="-1">
                <header class="painel-cabecalho">
                    <div>
                        <span class="marca-painel">ROTA FÁCIL</span>
                        <h2 id="vender-passagem-titulo">Registrar venda de passagem</h2>
                    </div>
                    <a class="fechar-painel" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}" aria-label="Fechar">×</a>
                </header>

                <form class="formulario" method="POST" action="{{ route('passagens.store') }}">
                    @csrf
                    <input type="hidden" name="viagem_id" value="{{ $viagem->id }}">

                    <div class="grade-formulario">
                        @if ($errors->any())
                            <div class="erro-formulario largura-total">
                                @foreach ($errors->all() as $mensagem)
                                    <p>{{ $mensagem }}</p>
                                @endforeach
                            </div>
                        @endif

                        <label class="largura-total">
                            Passageiro
                            <select name="passageiro_id" required>
                                <option value="">Selecione o passageiro</option>
                                @foreach ($passageiros as $pa)
                                    <option value="{{ $pa->id }}" @selected((string) old('passageiro_id') === (string) $pa->id)>{{ $pa->nome }}</option>
                                @endforeach
                            </select>
                        </label>
                        <a class="link-cadastro largura-total" href="{{ route('passageiros.index') }}#novo-cliente">+ Cadastrar passageiro</a>

                        <p class="nota largura-total">{{ $viagem->origem }} → {{ $viagem->destino }} • {{ $viagem->saida->format('d/m/Y • H:i') }}</p>

                        <div class="largura-total">
                            <fieldset class="campo-assentos">
                                <legend class="sr-only">Mapa de assentos</legend>
                                <p class="frente">FRENTE DO VEÍCULO</p>
                                <div class="assentos">
                                    @for ($n = 1; $n <= $viagem->capacidade; $n++)
                                        <label class="assento">
                                            <input aria-label="Assento {{ sprintf('%02d', $n) }}" type="radio" name="assento" value="{{ $n }}"
                                                @disabled(in_array($n, $ocupados))
                                                @checked((string) old('assento') === (string) $n)>
                                            <span>{{ sprintf('%02d', $n) }}</span>
                                        </label>
                                    @endfor
                                </div>
                            </fieldset>
                            <div class="legenda">
                                <span><i class="livre"></i> Livre</span>
                                <span><i class="ocupado"></i> Ocupado</span>
                                <span><i class="selecionado"></i> Selecionado</span>
                            </div>
                        </div>

                        <label>
                            Valor da passagem (R$)
                            <input type="number" name="valor" value="{{ old('valor', number_format($viagem->valor, 2, '.', '')) }}" min="0.01" step="0.01" required>
                        </label>
                        <label>
                            Situação do pagamento
                            <select name="situacao_pagamento" id="situacao-pagamento">
                                <option value="pago" @selected(old('situacao_pagamento', 'pago') === 'pago')>Pago</option>
                                <option value="fiado" @selected(old('situacao_pagamento') === 'fiado')>Fiado • ainda não recebido</option>
                                <option value="parcial" @selected(old('situacao_pagamento') === 'parcial')>Pago parcialmente</option>
                            </select>
                        </label>
                        <label class="forma-pagamento-principal" id="bloco-forma">
                            Forma de pagamento
                            <select name="forma_pagamento">
                                <option value="dinheiro" @selected(old('forma_pagamento') === 'dinheiro')>Dinheiro</option>
                                <option value="pix" @selected(old('forma_pagamento', 'pix') === 'pix')>Pix</option>
                                <option value="cartao" @selected(old('forma_pagamento') === 'cartao')>Cartão</option>
                            </select>
                        </label>
                        <div class="valor-parcial largura-total" id="bloco-parcial">
                            <label>
                                Valor já recebido (R$)
                                <input type="number" name="valor_recebido" value="{{ old('valor_recebido') }}" min="0.01" step="0.01" placeholder="Ex.: 10,00">
                            </label>
                            <small>O restante fica a receber (fiado), na forma de pagamento escolhida acima para a entrada.</small>
                        </div>
                    </div>

                    <div class="acoes">
                        <a class="botao secundario" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}">Cancelar</a>
                        <button type="submit" class="botao">Registrar passagem</button>
                    </div>
                </form>

                <script>
                    (function () {
                        var situacao = document.getElementById('situacao-pagamento');
                        var parcial = document.getElementById('bloco-parcial');
                        var forma = document.getElementById('bloco-forma');

                        function atualizar() {
                            parcial.style.display = situacao.value === 'parcial' ? 'block' : 'none';
                            forma.style.display = situacao.value === 'fiado' ? 'none' : '';
                        }

                        situacao.addEventListener('change', atualizar);
                        atualizar();
                    })();
                </script>
            </section>
        </section>

        @foreach ($passagens->where('situacao', 'ativa') as $p)
            {{-- Cancelar passagem (UC-09) --}}
            <section class="janela" id="cancelar-passagem-{{ $p->id }}" aria-label="Cancelar passagem">
                <a class="fundo-janela" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}" aria-label="Fechar painel"></a>
                <section class="painel" role="dialog" aria-modal="true" aria-labelledby="titulo-cancelar-{{ $p->id }}">
                    <header class="painel-cabecalho">
                        <div>
                            <span class="marca-painel">ROTA FÁCIL</span>
                            <h2 id="titulo-cancelar-{{ $p->id }}">Cancelar passagem</h2>
                        </div>
                        <a class="fechar-painel" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}" aria-label="Fechar">×</a>
                    </header>

                    <form class="formulario" method="POST" action="{{ route('passagens.cancelar', $p->id) }}">
                        @csrf
                        <div class="grade-formulario">
                            <p class="nota largura-total">
                                {{ $p->passageiro->nome }} · assento {{ sprintf('%02d', $p->assento) }} · {{ $dinheiro($p->valor) }}.<br>
                                O cancelamento libera o assento e mantém o histórico da passagem e dos pagamentos.
                                @if ($p->recebido > 0)
                                    Já foram recebidos {{ $dinheiro($p->recebido) }}: a devolução deve ser combinada com o passageiro, fora do sistema.
                                @endif
                            </p>
                            <label class="largura-total">
                                Motivo do cancelamento
                                <small>opcional</small>
                                <input type="text" name="motivo" maxlength="200">
                            </label>
                        </div>
                        <div class="acoes">
                            <a class="botao secundario" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}">Voltar</a>
                            <button type="submit" class="botao">Cancelar passagem</button>
                        </div>
                    </form>
                </section>
            </section>

            {{-- Receber fiado --}}
            @if ($p->saldo > 0)
                @php
                    $usarOld = session('editando') == $p->id;
                    $erros = $errors->getBag('pagamento_' . $p->id);
                @endphp
                <section class="janela pagamento-passagem" id="pagamento-{{ $p->id }}" aria-label="Painel: pagamento">
                    <a class="fundo-janela" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}" aria-label="Fechar painel"></a>
                    <section class="painel" role="dialog" aria-modal="true" aria-labelledby="pagamento-{{ $p->id }}-titulo" tabindex="-1">
                        <header class="painel-cabecalho">
                            <div>
                                <span class="marca-painel">ROTA FÁCIL</span>
                                <h2 id="pagamento-{{ $p->id }}-titulo">Registrar pagamento</h2>
                            </div>
                            <a class="fechar-painel" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}" aria-label="Fechar">×</a>
                        </header>

                        <form class="formulario" method="POST" action="{{ route('passagens.receber', $p->id) }}">
                            @csrf
                            <section class="resumo-recebimento" aria-label="Saldo da passagem">
                                <p>{{ $p->passageiro->nome }} · Passagem · Assento {{ sprintf('%02d', $p->assento) }}</p>
                                <strong>{{ $dinheiro($p->saldo) }}</strong>
                                <small>Saldo a receber</small>
                            </section>

                            <div class="grade-formulario">
                                @if ($erros->any())
                                    <div class="erro-formulario largura-total">
                                        @foreach ($erros->all() as $mensagem)
                                            <p>{{ $mensagem }}</p>
                                        @endforeach
                                    </div>
                                @endif

                                <label class="largura-total">
                                    Forma de pagamento
                                    <select name="forma">
                                        @php($forma = $usarOld ? old('forma', 'pix') : 'pix')
                                        <option value="pix" @selected($forma === 'pix')>Pix</option>
                                        <option value="dinheiro" @selected($forma === 'dinheiro')>Dinheiro</option>
                                        <option value="cartao" @selected($forma === 'cartao')>Cartão</option>
                                    </select>
                                </label>
                                <label class="largura-total">
                                    Valor a pagar agora (R$)
                                    <input type="number" name="valor" value="{{ $usarOld ? old('valor') : number_format($p->saldo, 2, '.', '') }}" min="0.01" max="{{ number_format($p->saldo, 2, '.', '') }}" step="0.01" required>
                                </label>
                            </div>

                            <p class="nota">Você pode receber só parte do saldo: informe o valor pago agora e o restante continua a receber. Este registro não realiza cobranças por Pix ou cartão.</p>
                            <div class="acoes">
                                <a class="botao secundario" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}">Cancelar</a>
                                <button class="botao" type="submit">Confirmar pagamento</button>
                            </div>
                        </form>
                    </section>
                </section>
            @endif
        @endforeach
    @endif
@endsection
