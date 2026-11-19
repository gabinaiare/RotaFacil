@extends('layouts.app')

@section('titulo', 'Encomendas')
@section('classe', 'pagina-encomendas')

@section('conteudo')
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $classeSelo = ['cadastrada' => 'pendente', 'em_transporte' => 'neutro', 'disponivel_retirada' => 'neutro', 'entregue' => ''];
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Encomendas</h1>
            <p class="introducao">Cadastre, consulte pelo código e acompanhe até a entrega.</p>
        </div>
        <a class="botao" href="#nova-encomenda">+ Nova encomenda</a>
    </div>

    @if (session('status'))
        <p class="sucesso-formulario">{{ session('status') }}</p>
    @endif
    @if (session('erro'))
        <p class="erro-formulario">{{ session('erro') }}</p>
    @endif

    <section class="resumo-verde">
        <div>
            <small>Em acompanhamento</small>
            <strong>{{ $emAcompanhamento }}</strong>
        </div>

        <div>
            <small>Entregues</small>
            <strong>{{ $entregues }}</strong>
        </div>
    </section>

    <section class="cartao bloco filtros">
        <form method="GET" action="{{ route('encomendas.index') }}">
            <label>
                Código ou descrição
                <input type="text" name="busca" value="{{ $busca }}" placeholder="ENC-010011, De ou Para">
            </label>

            <label>
                Situação
                <select name="situacao">
                    <option value="">Todas</option>
                    @foreach (\App\Models\Encomenda::SITUACOES as $chave => $rotulo)
                        <option value="{{ $chave }}" @selected($situacao === $chave)>{{ $rotulo }}</option>
                    @endforeach
                </select>
            </label>

            <button type="submit" class="botao">Consultar</button>
            @if ($busca !== '' || $situacao !== '')
                <a href="{{ route('encomendas.index') }}">Limpar filtros</a>
            @endif
        </form>
    </section>

    @forelse ($encomendas as $e)
        <article class="cartao bloco encomenda-card">
            <div class="linha">
                <span class="selo neutro">{{ $e->codigo }}</span>
                <span class="selo {{ $classeSelo[$e->situacao] ?? '' }}">{{ $e->situacao_rotulo }}</span>
            </div>

            <h2>{{ $e->descricao }}</h2>
            <p class="texto-secundario">De: <strong>{{ $e->remetente_nome }}</strong><br>Para: <strong>{{ $e->destinatario_nome }}</strong></p>
            <p class="nota">
                {{ $e->origem }} → {{ $e->destino }}
                @if ($e->viagem)
                    <br>{{ $e->viagem->saida->format('d/m/Y') }} • {{ $e->viagem->saida->format('H:i') }}
                @endif
                @if ($e->data_entrega)
                    <br>Entregue em {{ $e->data_entrega->format('d/m/Y • H:i') }}
                @endif
            </p>

            @if ((float) $e->valor > 0)
                <div class="valor-encomenda">
                    <small>Transporte</small>
                    <h3>{{ $dinheiro($e->valor) }}</h3>
                    <div class="duas-colunas">
                        <div>
                            <small>Recebido</small>
                            <h3 class="verde">{{ $dinheiro($e->recebido) }}</h3>
                        </div>

                        <div>
                            <small>Fiado</small>
                            <h3 class="amarelo">{{ $dinheiro($e->saldo) }}</h3>
                        </div>
                    </div>
                </div>
            @endif

            <div class="acoes-card">
                <a href="{{ route('encomendas.comprovante', $e->id) }}" target="_blank">Ver comprovante</a>
                <a class="botao secundario" href="#detalhe-encomenda-{{ $e->id }}">Detalhes e histórico</a>

                @if ($e->situacao !== 'entregue')
                    <a href="#editar-encomenda-{{ $e->id }}">Editar</a>
                    <a class="botao" href="#status-encomenda-{{ $e->id }}">Atualizar status</a>
                    <form method="POST" action="{{ route('encomendas.status', $e->id) }}" style="display:inline"
                          onsubmit="return confirm('Confirmar que a encomenda foi entregue a {{ e($e->destinatario_nome) }}?')">
                        @csrf
                        <input type="hidden" name="situacao" value="entregue">
                        <input type="hidden" name="observacao" value="Entrega confirmada pelo motorista.">
                        <button type="submit" class="link-botao">Confirmar entrega</button>
                    </form>
                @endif

                @if ($e->saldo > 0)
                    <a href="#pagamento-encomenda-{{ $e->id }}">Receber</a>
                @endif
            </div>
        </article>
    @empty
        <p class="texto-secundario">
            @if ($busca !== '' || $situacao !== '')
                Nenhuma encomenda encontrada com esses filtros.
            @else
                Você ainda não cadastrou nenhuma encomenda. Toque em "+ Nova encomenda" para começar.
            @endif
        </p>
    @endforelse
@endsection

@section('janelas')
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $ordem = array_keys(\App\Models\Encomenda::SITUACOES);
    @endphp

    @include('encomendas._formulario', [
        'idPainel' => 'nova-encomenda',
        'titulo' => 'Nova encomenda',
        'acao' => route('encomendas.store'),
        'metodo' => 'POST',
        'encomenda' => null,
        'bag' => 'default',
        'usarOld' => ! session('editando'),
        'botao' => 'Salvar encomenda',
    ])

    @foreach ($encomendas as $e)
        @php
            $proximas = array_slice(\App\Models\Encomenda::SITUACOES, array_search($e->situacao, $ordem) + 1, null, true);
        @endphp

        {{-- Detalhes e histórico (UC-11) --}}
        <section class="janela" id="detalhe-encomenda-{{ $e->id }}" aria-label="Painel: detalhe encomenda">
            <a class="fundo-janela" href="{{ route('encomendas.index') }}" aria-label="Fechar painel"></a>

            <section class="painel" role="dialog" aria-modal="true" aria-labelledby="detalhe-encomenda-{{ $e->id }}-titulo" tabindex="-1">
                <header class="painel-cabecalho">
                    <div>
                        <span class="marca-painel">ROTA FÁCIL</span>
                        <h2 id="detalhe-encomenda-{{ $e->id }}-titulo">Detalhes da encomenda</h2>
                    </div>
                    <a class="fechar-painel" href="{{ route('encomendas.index') }}" aria-label="Fechar detalhes da encomenda">×</a>
                </header>

                <span class="selo pendente">{{ $e->situacao_rotulo }}</span>
                <h2 class="espaco">{{ $e->descricao }}</h2>

                <dl class="dados">
                    <div><dt>Código</dt><dd>{{ $e->codigo }}</dd></div>
                    <div><dt>De</dt><dd>{{ $e->remetente_nome }} · {{ $e->remetente_telefone_formatado }}</dd></div>
                    <div><dt>Para</dt><dd>{{ $e->destinatario_nome }} · {{ $e->destinatario_telefone_formatado }}</dd></div>
                    <div><dt>Trajeto</dt><dd>{{ $e->origem }} → {{ $e->destino }}</dd></div>
                    @if ($e->viagem)
                        <div><dt>Viagem</dt><dd>{{ $e->viagem->saida->format('d/m/Y • H:i') }}</dd></div>
                    @endif
                    <div><dt>Transporte</dt><dd>{{ (float) $e->valor > 0 ? $dinheiro($e->valor) : 'Sem valor' }}</dd></div>
                    <div><dt>Pagamento</dt><dd>{{ $e->pagamento_rotulo }}</dd></div>
                </dl>

                <h2 class="espaco">Histórico</h2>
                @foreach ($e->movimentacoes->sortByDesc('id') as $m)
                    <p class="nota">
                        {{ $m->created_at->format('d/m/Y • H:i') }}<br>
                        {{ $m->situacao_anterior ? \App\Models\Encomenda::SITUACOES[$m->situacao_anterior] . ' → ' : '' }}<strong>{{ \App\Models\Encomenda::SITUACOES[$m->nova_situacao] ?? $m->nova_situacao }}</strong>
                        @if ($m->observacao)
                            <br>{{ $m->observacao }}
                        @endif
                    </p>
                @endforeach

                <div class="acoes">
                    <a class="botao secundario" href="{{ route('encomendas.comprovante', $e->id) }}" target="_blank">Comprovante</a>
                    <a class="botao secundario" href="{{ route('encomendas.index') }}">Voltar</a>
                    @if ($e->situacao !== 'entregue')
                        <a class="botao" href="#status-encomenda-{{ $e->id }}">Atualizar status</a>
                    @endif
                </div>
            </section>
        </section>

        @if ($e->situacao !== 'entregue')
            @include('encomendas._formulario', [
                'idPainel' => 'editar-encomenda-' . $e->id,
                'titulo' => 'Editar encomenda',
                'acao' => route('encomendas.update', $e->id),
                'metodo' => 'PUT',
                'encomenda' => $e,
                'bag' => 'encomenda_' . $e->id,
                'usarOld' => session('editando') == $e->id,
                'botao' => 'Salvar alterações',
            ])

            {{-- Atualizar situação (UC-12) --}}
            <section class="janela" id="status-encomenda-{{ $e->id }}" aria-label="Painel: status encomenda">
                <a class="fundo-janela" href="{{ route('encomendas.index') }}" aria-label="Fechar painel"></a>

                <section class="painel" role="dialog" aria-modal="true" aria-labelledby="status-encomenda-{{ $e->id }}-titulo" tabindex="-1">
                    <header class="painel-cabecalho">
                        <div>
                            <span class="marca-painel">ROTA FÁCIL</span>
                            <h2 id="status-encomenda-{{ $e->id }}-titulo">Atualizar situação</h2>
                        </div>
                        <a class="fechar-painel" href="{{ route('encomendas.index') }}" aria-label="Fechar atualizar situação">×</a>
                    </header>

                    <form class="formulario" method="POST" action="{{ route('encomendas.status', $e->id) }}">
                        @csrf
                        <div class="grade-formulario">
                            <p class="nota largura-total">{{ $e->codigo }} • {{ $e->descricao }}<br>Situação atual: {{ $e->situacao_rotulo }}</p>
                            <label>
                                Nova situação
                                <select name="situacao">
                                    @foreach ($proximas as $chave => $rotulo)
                                        <option value="{{ $chave }}">{{ $rotulo }}</option>
                                    @endforeach
                                </select>
                            </label>

                            <label>
                                Observação
                                <small>opcional</small>
                                <input type="text" name="observacao" maxlength="200">
                            </label>

                            <p class="nota largura-total">A situação só pode avançar. Ao marcar como "Entregue", a encomenda é encerrada.</p>
                        </div>
                        <div class="acoes">
                            <a class="botao secundario" href="{{ route('encomendas.index') }}">Cancelar</a>
                            <button type="submit" class="botao">Salvar situação</button>
                        </div>
                    </form>
                </section>
            </section>
        @endif

        {{-- Receber pagamento --}}
        @if ($e->saldo > 0)
            @php
                $usarOld = session('editando') == $e->id;
                $erros = $errors->getBag('pagamento_' . $e->id);
                $forma = $usarOld ? old('forma', 'pix') : 'pix';
            @endphp
            <section class="janela" id="pagamento-encomenda-{{ $e->id }}" aria-label="Painel: pagamento encomenda">
                <a class="fundo-janela" href="{{ route('encomendas.index') }}" aria-label="Fechar painel"></a>

                <section class="painel" role="dialog" aria-modal="true" aria-labelledby="pagamento-encomenda-{{ $e->id }}-titulo" tabindex="-1">
                    <header class="painel-cabecalho">
                        <div>
                            <span class="marca-painel">ROTA FÁCIL</span>
                            <h2 id="pagamento-encomenda-{{ $e->id }}-titulo">Receber pagamento</h2>
                        </div>
                        <a class="fechar-painel" href="{{ route('encomendas.index') }}" aria-label="Fechar receber pagamento">×</a>
                    </header>

                    <form class="formulario" method="POST" action="{{ route('encomendas.receber', $e->id) }}">
                        @csrf
                        <div class="grade-formulario">
                            @if ($erros->any())
                                <div class="erro-formulario largura-total">
                                    @foreach ($erros->all() as $mensagem)
                                        <p>{{ $mensagem }}</p>
                                    @endforeach
                                </div>
                            @endif

                            <p class="nota largura-total">{{ $e->codigo }} • {{ $e->descricao }}<br>Valor do transporte: {{ $dinheiro($e->valor) }} • Pendente: {{ $dinheiro($e->saldo) }}</p>
                            <label>
                                Valor recebido (R$)
                                <input type="number" name="valor" value="{{ $usarOld ? old('valor') : number_format($e->saldo, 2, '.', '') }}" min="0.01" max="{{ number_format($e->saldo, 2, '.', '') }}" step="0.01" required>
                            </label>

                            <label>
                                Forma de pagamento
                                <select name="forma">
                                    <option value="pix" @selected($forma === 'pix')>Pix</option>
                                    <option value="dinheiro" @selected($forma === 'dinheiro')>Dinheiro</option>
                                    <option value="cartao" @selected($forma === 'cartao')>Cartão</option>
                                </select>
                            </label>
                        </div>

                        <div class="acoes">
                            <a class="botao secundario" href="{{ route('encomendas.index') }}">Cancelar</a>
                            <button type="submit" class="botao">Registrar recebimento</button>
                        </div>
                    </form>
                </section>
            </section>
        @endif
    @endforeach
@endsection
