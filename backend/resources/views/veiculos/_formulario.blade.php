@php
    $erros = $errors->getBag($bag);

    // Valor do campo: o que o usuário digitou (se houve erro) ou o que está salvo
    $campo = function (string $nome, $padrao = '') use ($veiculo, $usarOld) {
        $base = $veiculo ? ($veiculo->{$nome} ?? $padrao) : $padrao;

        return $usarOld ? old($nome, $base) : $base;
    };

    $tipo = $campo('tipo', 'van');
    $situacao = $campo('situacao', 'disponivel');
@endphp

<section class="janela" id="{{ $idPainel }}" aria-label="Painel: {{ $titulo }}">
    <a class="fundo-janela" href="{{ route('veiculos.index') }}" aria-label="Fechar painel"></a>

    <section class="painel" role="dialog" aria-modal="true" aria-labelledby="{{ $idPainel }}-titulo" tabindex="-1">

        <header class="painel-cabecalho">
            <div>
                <span class="marca-painel">ROTA FÁCIL</span>
                <h2 id="{{ $idPainel }}-titulo">{{ $titulo }}</h2>
            </div>
            <a class="fechar-painel" href="{{ route('veiculos.index') }}" aria-label="Fechar">×</a>
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
                    Modelo
                    <input type="text" name="modelo" value="{{ $campo('modelo') }}" placeholder="Mercedes-Benz Sprinter" maxlength="80" required>
                </label>
                <label>
                    Tipo
                    <select name="tipo">
                        <option value="van" @selected($tipo === 'van')>Van</option>
                        <option value="onibus" @selected($tipo === 'onibus')>Ônibus</option>
                    </select>
                </label>
                <label>
                    Placa
                    <input type="text" name="placa" value="{{ $campo('placa') }}" placeholder="ABC1D23" maxlength="8" style="text-transform: uppercase" required>
                </label>
                <label>
                    Capacidade de passageiros
                    <input type="number" name="capacidade" value="{{ $campo('capacidade') }}" placeholder="16" min="1" max="60" required>
                </label>
                <label>
                    Situação
                    <select name="situacao">
                        <option value="disponivel" @selected($situacao === 'disponivel')>Disponível</option>
                        <option value="indisponivel" @selected($situacao === 'indisponivel')>Indisponível</option>
                    </select>
                </label>
                <p class="nota largura-total">A placa identifica o veículo. Somente veículos disponíveis podem receber novas viagens.</p>
            </div>

            <div class="acoes">
                <a class="botao secundario" href="{{ route('veiculos.index') }}">Cancelar</a>
                <button type="submit" class="botao">{{ $botao }}</button>
            </div>
        </form>
    </section>
</section>
