@php
    $erros = $errors->getBag($bag);

    // Valor do campo: o que o usuário digitou (se houve erro) ou o que está salvo.
    // $exibir é o nome do campo "bonito" (ex.: telefone_formatado) usado só na edição.
    $campo = function (string $nome, ?string $exibir = null) use ($passageiro, $usarOld) {
        $base = $passageiro ? ($passageiro->{$exibir ?? $nome} ?? '') : '';

        return $usarOld ? old($nome, $base) : $base;
    };
@endphp

<section class="janela" id="{{ $idPainel }}" aria-label="Painel: {{ $titulo }}">
    <a class="fundo-janela" href="{{ route('passageiros.index') }}" aria-label="Fechar painel"></a>

    <section class="painel" role="dialog" aria-modal="true" aria-labelledby="{{ $idPainel }}-titulo" tabindex="-1">

        <header class="painel-cabecalho">
            <div>
                <span class="marca-painel">ROTA FÁCIL</span>
                <h2 id="{{ $idPainel }}-titulo">{{ $titulo }}</h2>
            </div>
            <a class="fechar-painel" href="{{ route('passageiros.index') }}" aria-label="Fechar">×</a>
        </header>

        <form class="formulario" method="POST" action="{{ $acao }}">
            @csrf
            @if ($metodo === 'PUT')
                @method('PUT')
            @endif

            <div class="grade-formulario">
                @if ($erros->any())
                    <div class="erro-formulario largura-total">
                        @foreach ($erros->all() as $mensagem)
                            <p>{{ $mensagem }}</p>
                        @endforeach
                    </div>
                @endif

                <label>
                    Nome completo
                    <input type="text" name="nome" value="{{ $campo('nome') }}" placeholder="Nome do passageiro" maxlength="120" required>
                </label>

                <label>
                    WhatsApp com DDD
                    <input type="tel" name="telefone" value="{{ $campo('telefone', 'telefone_formatado') }}" placeholder="(84) 99999-9999" required>
                </label>

                <label>
                    CPF
                    <small>opcional</small>
                    <input type="text" name="cpf" value="{{ $campo('cpf', 'cpf_formatado') }}" placeholder="000.000.000-00" inputmode="numeric">
                </label>

                <label>
                    E-mail
                    <small>opcional</small>
                    <input type="email" name="email" value="{{ $campo('email') }}" maxlength="120">
                </label>

                <label class="largura-total">
                    Endereço
                    <small>opcional</small>
                    <input type="text" name="endereco" value="{{ $campo('endereco') }}" placeholder="Rua, número e bairro" maxlength="200">
                </label>

                <p class="nota largura-total">As passagens e os pagamentos formam o histórico deste passageiro.</p>
            </div>

            <div class="acoes">
                <a class="botao secundario" href="{{ route('passageiros.index') }}">Cancelar</a>
                <button type="submit" class="botao">{{ $botao }}</button>
            </div>
        </form>
    </section>
</section>
