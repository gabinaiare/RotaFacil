@extends('layouts.app')

@section('titulo', 'Visão geral')

@section('conteudo')
    <h1>Olá, {{ auth()->user()->primeiro_nome }}</h1>
    <p class="introducao">Seu transporte, seus clientes e suas contas em um só lugar.</p>
    <a class="botao" href="{{ route('viagens.index') }}">+ Nova rota</a>

    <div class="atalhos">
        <a href="{{ route('passagens.index') }}"><span class="icone icone-bilhete" aria-hidden="true"></span><strong>Vender passagem</strong></a>
        <a href="{{ route('encomendas.index') }}"><span class="icone icone-encomendas" aria-hidden="true"></span><strong>Nova encomenda</strong></a>
        <a href="{{ route('passageiros.index') }}"><span class="icone icone-cliente" aria-hidden="true"></span><strong>Novo cliente</strong></a>
    </div>

    <section class="cartao lista-viagens">
        <div class="cabecalho-secao">
            <h2>Próximas viagens</h2>
            <a href="{{ route('viagens.index') }}">Todas as rotas</a>
        </div>

        @forelse ($viagens as $viagem)
            @php
                $vagas = $viagem->capacidade - $viagem->ocupados;
                $percentual = $viagem->capacidade > 0 ? min(100, round($viagem->ocupados / $viagem->capacidade * 100, 1)) : 0;
            @endphp
            <article class="viagem">
                <div class="linha">
                    <span class="selo">
                        @if ($vagas <= 0)
                            Lotada
                        @elseif ($vagas === 1)
                            1 vaga disponível
                        @else
                            {{ $vagas }} vagas disponíveis
                        @endif
                    </span>
                    <span class="texto-secundario">{{ $viagem->saida->translatedFormat('d M') }} · {{ $viagem->saida->format('H:i') }}</span>
                </div>

                <div class="trajeto">
                    <div>
                        <h3>{{ $viagem->origem }}</h3>
                        <small>Saída às {{ $viagem->saida->format('H:i') }}</small>
                    </div>
                    <span class="icone icone-viajar-por" aria-hidden="true"></span>
                    <div>
                        <h3>{{ $viagem->destino }}</h3>
                    </div>
                </div>

                <div class="linha">
                    <span class="texto-secundario tipo-veiculo"><span class="icone icone-bus" aria-hidden="true"></span>{{ $viagem->veiculo->tipo === 'van' ? 'Van' : 'Ônibus' }} · {{ $viagem->capacidade }} assentos</span>
                    <div>
                        <b class="preco">R$ {{ number_format($viagem->valor, 2, ',', '.') }}</b>
                        <small>/pessoa</small>
                    </div>
                </div>

                <div class="ocupacao">
                    <div class="barra-ocupacao">
                        <span style="width: {{ $percentual }}%"></span>
                    </div>
                    <small>{{ $viagem->ocupados }}/{{ $viagem->capacidade }} ocupados</small>
                </div>

                <div class="linha">
                    <span></span>
                    <a href="{{ route('viagens.index') }}">Ver viagem</a>
                </div>
            </article>
        @empty
            <p class="texto-secundario">Nenhuma viagem programada. Cadastre a primeira em "+ Nova rota".</p>
        @endforelse
    </section>

    <section class="destaque">
        <span class="icone icone-rota-destaque icone-viajar-por" aria-hidden="true"></span>
        <small>NO SEU RITMO</small>
        <h2>Você escolhe a rota.<br>A gente organiza.</h2>
        <p>Defina destinos, horários e valores. Cada viagem com o transporte e os assentos que você precisa.</p>
        <a class="botao" href="{{ route('viagens.index') }}">+ Cadastrar uma rota</a>
    </section>

    <section class="cartao acesso-rapido">
        <div class="cabecalho-secao">
            <h2>Acesso rápido</h2>
        </div>

        <a class="item-acesso-rapido" href="{{ route('veiculos.index') }}">
            <span class="icone icone-bus" aria-hidden="true"></span>
            <div>
                <strong>Cadastrar veículo</strong>
                <small>Transporte e capacidade</small>
            </div>
            <b>+</b>
        </a>

        <a class="item-acesso-rapido" href="{{ route('passageiros.index') }}">
            <span class="icone icone-cliente" aria-hidden="true"></span>
            <div>
                <strong>Meus passageiros</strong>
                <small>Passageiros e pagamentos</small>
            </div>
            <b>+</b>
        </a>

        <a class="item-acesso-rapido" href="{{ route('viagens.index') }}">
            <span class="icone icone-cadeira-de-carro" aria-hidden="true"></span>
            <div>
                <strong>Gerenciar assentos</strong>
                <small>Veja a ocupação de cada viagem</small>
            </div>
            <b>+</b>
        </a>
    </section>

    <div class="cabecalho-secao cabecalho-veiculos">
        <h2>Meus veículos</h2>
        <a href="{{ route('veiculos.index') }}">Ver todos</a>
    </div>

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
            </div>
            <a class="editar" href="{{ route('veiculos.index') }}">Editar veículo</a>
        </article>
    @empty
        <p class="texto-secundario">Nenhum veículo cadastrado ainda.</p>
    @endforelse
@endsection
