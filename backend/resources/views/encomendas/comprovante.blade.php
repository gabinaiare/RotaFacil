<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovante {{ $encomenda->codigo }} • Rota Fácil</title>
    <link rel="icon" href="{{ asset('assets/favicon-rota-facil.png') }}">
    <link rel="stylesheet" href="{{ asset('css/comprovante.css') }}">
</head>

<body>
    @php
        $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
        $temValor = (float) $encomenda->valor > 0;
    @endphp

    <header class="barra-impressao">
        <a class="voltar-encomendas" href="{{ route('encomendas.index') }}" style="display:inline">← Voltar às encomendas</a>
    </header>

    <main class="area-recibo">
        <article class="recibo recibo-padrao" id="{{ $encomenda->codigo }}" aria-labelledby="titulo-recibo">
            <header class="marca-recibo">
                <img src="{{ asset('assets/logo-rota-facil.png') }}" alt="Rota Fácil">
                <span>COMPROVANTE DE ENCOMENDA</span>
            </header>

            <div class="cabecalho-recibo">
                <h1 id="titulo-recibo">Sua encomenda está registrada</h1>
                <p>Comprovante {{ $encomenda->codigo }}</p>
            </div>

            <section class="rota-recibo" aria-label="Trajeto">
                <strong>{{ $encomenda->origem }}</strong><span aria-hidden="true">→</span><strong>{{ $encomenda->destino }}</strong>
            </section>

            <dl class="dados-recibo">
                <div><dt>De</dt><dd>{{ $encomenda->remetente_nome }}</dd></div>
                <div><dt>Para</dt><dd>{{ $encomenda->destinatario_nome }}</dd></div>
                <div><dt>Descrição</dt><dd>{{ $encomenda->descricao }}</dd></div>
                @if ($encomenda->viagem)
                    <div><dt>Data da viagem</dt><dd>{{ $encomenda->viagem->saida->format('d/m/Y') }}</dd></div>
                    <div><dt>Saída</dt><dd>{{ $encomenda->viagem->saida->format('H:i') }}</dd></div>
                @endif
                <div><dt>Situação da encomenda</dt><dd>{{ $encomenda->situacao_rotulo }}</dd></div>
                @if ($temValor)
                    <div><dt>Valor do transporte</dt><dd>{{ $dinheiro($encomenda->valor) }}</dd></div>
                    <div><dt>Valor recebido</dt><dd>{{ $dinheiro($encomenda->recebido) }}</dd></div>
                    <div><dt>A receber</dt><dd>{{ $dinheiro($encomenda->saldo) }}</dd></div>
                @endif
                <div><dt>Motorista</dt><dd>{{ $motorista->name }}</dd></div>
            </dl>

            <footer class="rodape-recibo">
                <p>Comprovante não fiscal. O registro da encomenda não comprova a quitação dos valores indicados como “A receber”.</p>
                <strong>Rota Fácil • Cada motorista, seu próprio caminho.</strong>
            </footer>

            <nav class="retorno-recibo" aria-label="Ações do comprovante">
                <a href="{{ route('encomendas.index') }}">Voltar à encomenda</a>
                <a class="baixar-recibo" href="#" onclick="window.print(); return false;">Imprimir / salvar PDF</a>
            </nav>
        </article>
    </main>
</body>
</html>
