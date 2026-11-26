@extends('layouts.app')

@section('titulo', 'Meus veículos')
@section('classe', 'pagina-veiculos')

@section('conteudo')
    <div class="titulo-pagina">
        <div>
            <h1>Meus veículos</h1>
            <p class="introducao">Cadastre o transporte e a capacidade de cada veículo.</p>
        </div>
        <a class="botao" href="#novo-veiculo">+ Novo veículo</a>
    </div>

    @if (session('status'))
        <p class="sucesso-formulario">{{ session('status') }}</p>
    @endif

    <div class="lista-veiculos">
        @forelse ($veiculos as $veiculo)
            <article class="veiculo cartao">
                <span class="selo disponibilidade">● {{ $veiculo->situacao === 'disponivel' ? 'Disponível' : 'Indisponível' }}</span>
                <div class="veiculo-cabecalho">
                    <span class="icone-veiculo"><span class="icone icone-bus" aria-hidden="true"></span></span>
                    <div class="veiculo-dados">
                        <small>{{ $veiculo->tipo === 'van' ? 'VAN' : 'ÔNIBUS' }}</small>
                        <h3>{{ $veiculo->modelo }}</h3>
                        <p>{{ substr($veiculo->placa, 0, 3) }}-{{ substr($veiculo->placa, 3) }}</p>
                    </div>
                </div>

                <div class="etiquetas">
                    <span class="etiqueta"><span class="icone icone-cadeira-de-carro" aria-hidden="true"></span>{{ $veiculo->capacidade }} assentos</span>
                    <span class="etiqueta"><span class="icone icone-bilhete" aria-hidden="true"></span>Numerados</span>
                </div>
                <a class="editar" href="#editar-veiculo-{{ $veiculo->id }}">Editar veículo</a>
            </article>
        @empty
            <p class="texto-secundario">Você ainda não cadastrou nenhum veículo. Toque em "+ Novo veículo" para começar.</p>
        @endforelse
    </div>
@endsection

@section('janelas')
    @include('veiculos._formulario', [
        'idPainel' => 'novo-veiculo',
        'titulo' => 'Cadastrar veículo',
        'acao' => route('veiculos.store'),
        'metodo' => 'POST',
        'veiculo' => null,
        'bag' => 'default',
        'usarOld' => ! session('editando'),
        'botao' => 'Salvar veículo',
    ])

    @foreach ($veiculos as $veiculo)
        @include('veiculos._formulario', [
            'idPainel' => 'editar-veiculo-' . $veiculo->id,
            'titulo' => 'Editar veículo',
            'acao' => route('veiculos.update', $veiculo->id),
            'metodo' => 'PUT',
            'veiculo' => $veiculo,
            'bag' => 'veiculo_' . $veiculo->id,
            'usarOld' => session('editando') == $veiculo->id,
            'botao' => 'Salvar alterações',
        ])
    @endforeach
@endsection
