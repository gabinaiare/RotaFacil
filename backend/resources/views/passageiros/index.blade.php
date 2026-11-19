@extends('layouts.app')

@section('titulo', 'Passageiros')
@section('classe', 'pagina-clientes')

@section('conteudo')
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $link = fn (string $f) => route('passageiros.index', array_filter(['filtro' => $f === 'todos' ? null : $f, 'busca' => $busca]));
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Passageiros</h1>
            <p class="introducao">Seus clientes, históricos e pagamentos em um só lugar.</p>
        </div>
        <a class="botao" href="#novo-cliente">+ Novo passageiro</a>
    </div>

    @if (session('status'))
        <p class="sucesso-formulario">{{ session('status') }}</p>
    @endif

    <section class="resumo-verde">
        <div>
            <small>Passageiros cadastrados</small>
            <strong>{{ $total }}</strong>
        </div>
        <div>
            <small>Com fiado</small>
            <strong>{{ $comFiado }}</strong>
        </div>
    </section>

    <form method="GET" action="{{ route('passageiros.index') }}" role="search">
        @if ($filtro !== 'todos')
            <input type="hidden" name="filtro" value="{{ $filtro }}">
        @endif
        <label class="sr-only" for="busca-passageiro">Buscar passageiro</label>
        <input class="busca-passageiro" id="busca-passageiro" type="search" name="busca" value="{{ $busca }}" placeholder="Buscar nome, CPF ou WhatsApp">
    </form>

    <nav class="abas-visuais filtros-clientes" aria-label="Filtros de passageiros">
        <a class="{{ $filtro === 'todos' ? 'ativa' : '' }}" href="{{ $link('todos') }}">Todos <span>{{ $total }}</span></a>
        <a class="{{ $filtro === 'fiado' ? 'ativa' : '' }}" href="{{ $link('fiado') }}">Com fiado <span>{{ $comFiado }}</span></a>
        <a class="{{ $filtro === 'em-dia' ? 'ativa' : '' }}" href="{{ $link('em-dia') }}">Em dia <span>{{ $emDia }}</span></a>
    </nav>

    <div class="grade-cartoes">
        @forelse ($passageiros as $p)
            <article class="cartao bloco passageiro-card" data-situacao="{{ $p->fiado > 0 ? 'fiado' : 'em-dia' }}">
                <header class="linha">
                    <div class="pessoa">
                        <span class="avatar">{{ $p->iniciais }}</span>
                        <div>
                            <h2>{{ $p->nome }}</h2>
                            <small>{{ $p->telefone_formatado ?: 'WhatsApp não informado' }}</small>
                        </div>
                    </div>

                    @if ($p->fiado > 0)
                        <span class="selo pendente">Com fiado</span>
                    @else
                        <span class="selo">Em dia</span>
                    @endif
                </header>

                <section class="saldo-passageiro">
                    <small>Passagens</small>
                    <strong>{{ $p->passagens->count() }}</strong>
                    <div class="duas-colunas">
                        <div>
                            <small>Recebido</small>
                            <strong class="verde">{{ $dinheiro($p->recebido) }}</strong>
                        </div>

                        <div>
                            <small>Fiado</small>
                            <strong class="amarelo">{{ $dinheiro($p->fiado) }}</strong>
                        </div>
                    </div>
                </section>

                <p class="ultima-viagem">
                    @if ($p->ultima_viagem)
                        Última viagem registrada: {{ $p->ultima_viagem->format('d/m/Y') }}
                    @else
                        Nenhuma passagem registrada
                    @endif
                </p>

                <div class="acoes-passageiro">
                    <a href="#editar-cliente-{{ $p->id }}">Editar</a>
                </div>
            </article>
        @empty
            <p class="texto-secundario">
                @if ($busca !== '' || $filtro !== 'todos')
                    Nenhum passageiro encontrado com esses filtros.
                @else
                    Você ainda não cadastrou nenhum passageiro. Toque em "+ Novo passageiro" para começar.
                @endif
            </p>
        @endforelse
    </div>
@endsection

@section('janelas')
    @include('passageiros._formulario', [
        'idPainel' => 'novo-cliente',
        'titulo' => 'Cadastrar passageiro',
        'acao' => route('passageiros.store'),
        'metodo' => 'POST',
        'passageiro' => null,
        'bag' => 'default',
        'usarOld' => ! session('editando'),
        'botao' => 'Salvar',
    ])

    @foreach ($todos as $p)
        @include('passageiros._formulario', [
            'idPainel' => 'editar-cliente-' . $p->id,
            'titulo' => 'Editar passageiro',
            'acao' => route('passageiros.update', $p->id),
            'metodo' => 'PUT',
            'passageiro' => $p,
            'bag' => 'passageiro_' . $p->id,
            'usarOld' => session('editando') == $p->id,
            'botao' => 'Salvar passageiro',
        ])
    @endforeach
@endsection
