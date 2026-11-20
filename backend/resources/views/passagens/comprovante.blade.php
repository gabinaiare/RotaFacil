<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovante {{ $passagem->codigo }} • Rota Fácil</title>
    <link rel="icon" href="{{ asset('assets/favicon-rota-facil.png') }}">
    <link rel="stylesheet" href="{{ asset('css/comprovante.css') }}">
</head>

<body>
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $viagem = $passagem->viagem;
    @endphp

    <header class="barra-impressao">
        <a class="voltar-passagens" href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}">← Voltar às passagens</a>
    </header>

    <main class="area-recibo">
        <article class="recibo recibo-padrao" id="{{ $passagem->codigo }}" aria-labelledby="titulo-recibo">
            <header class="marca-recibo">
                <img src="{{ asset('assets/logo-rota-facil.png') }}" alt="Rota Fácil">
                <span>COMPROVANTE DE PASSAGEM</span>
            </header>

            <div class="cabecalho-recibo">
                <h1 id="titulo-recibo">
                    {{ $passagem->situacao === 'ativa' ? 'Sua viagem está registrada' : 'Passagem cancelada' }}
                </h1>
                <p>Comprovante {{ $passagem->codigo }}</p>
            </div>

            <section class="rota-recibo" aria-label="Trajeto">
                <strong>{{ $viagem->origem }}</strong><span aria-hidden="true">→</span><strong>{{ $viagem->destino }}</strong>
            </section>

            <dl class="dados-recibo">
                <div><dt>Passageiro</dt><dd>{{ $passagem->passageiro->nome }}</dd></div>
                <div><dt>Data</dt><dd>{{ $viagem->saida->format('d/m/Y') }}</dd></div>
                <div><dt>Saída</dt><dd>{{ $viagem->saida->format('H:i') }}</dd></div>
                <div><dt>Veículo</dt><dd>{{ $viagem->veiculo->modelo }}</dd></div>
                <div><dt>Assento</dt><dd>{{ sprintf('%02d', $passagem->assento) }}</dd></div>
                <div><dt>Valor da passagem</dt><dd>{{ $dinheiro($passagem->valor) }}</dd></div>
                <div><dt>Situação do pagamento</dt><dd>{{ $passagem->situacao_pagamento }}</dd></div>
                <div><dt>Valor recebido</dt><dd>{{ $dinheiro($passagem->recebido) }}</dd></div>
                @if ($passagem->saldo > 0)
                    <div><dt>A receber</dt><dd>{{ $dinheiro($passagem->saldo) }}</dd></div>
                @endif
                <div><dt>Motorista</dt><dd>{{ $motorista->name }}</dd></div>
            </dl>

            <p class="informacao-recibo">Apresente este comprovante no embarque. Caso não possa viajar, avise ao motorista.</p>

            <footer class="rodape-recibo">
                <p>Comprovante não fiscal. O registro da passagem não comprova a quitação de valores indicados como “A receber”.</p>
                <strong>Rota Fácil • Cada motorista, seu próprio caminho.</strong>
            </footer>

            <nav class="retorno-recibo" aria-label="Ações do comprovante">
                <a href="{{ route('passagens.index', ['viagem' => $viagem->id]) }}">Voltar ao registro</a>
                <a class="baixar-recibo" href="#" onclick="window.print(); return false;">Imprimir / salvar PDF</a>
            </nav>
        </article>
    </main>
</body>
</html>
