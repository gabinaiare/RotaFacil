@php
    $erros = $errors->getBag($bag);

    $base = $viagem ? [
        'origem' => $viagem->origem,
        'destino' => $viagem->destino,
        'data_saida' => $viagem->saida->format('Y-m-d'),
        'horario_saida' => $viagem->saida->format('H:i'),
        'veiculo_id' => $viagem->veiculo_id,
        'valor' => number_format($viagem->valor, 2, '.', ''),
    ] : [];

    // O que o usuário digitou (se houve erro) ou o que está salvo
    $campo = fn (string $nome) => $usarOld ? old($nome, $base[$nome] ?? '') : ($base[$nome] ?? '');
    $veiculoEscolhido = (string) $campo('veiculo_id');
@endphp

<section class="janela" id="{{ $idPainel }}" aria-label="Painel: {{ $titulo }}">
    <a class="fundo-janela" href="{{ route('viagens.index') }}" aria-label="Fechar painel"></a>

    <section class="painel" role="dialog" aria-modal="true" aria-labelledby="{{ $idPainel }}-titulo" tabindex="-1">

        <header class="painel-cabecalho">
            <div>
                <span class="marca-painel">ROTA FÁCIL</span>
                <h2 id="{{ $idPainel }}-titulo">{{ $titulo }}</h2>
            </div>
            <a class="fechar-painel" href="{{ route('viagens.index') }}" aria-label="Fechar">×</a>
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
                    Origem
                    <input type="text" name="origem" value="{{ $campo('origem') }}" placeholder="São Miguel" maxlength="80" required>
                </label>
                <label>
                    Destino
                    <input type="text" name="destino" value="{{ $campo('destino') }}" placeholder="Pau dos Ferros" maxlength="80" required>
                </label>
                <label>
                    Data de saída
                    <input type="date" name="data_saida" value="{{ $campo('data_saida') }}" required>
                </label>
                <label>
                    Horário de saída
                    <input type="time" name="horario_saida" value="{{ $campo('horario_saida') }}" required>
                </label>
                <label>
                    Veículo
                    <select name="veiculo_id" required>
                        <option value="">Selecione o veículo</option>
                        @foreach ($veiculos as $v)
                            @if ($v->situacao === 'disponivel' || ($viagem && $viagem->veiculo_id === $v->id))
                                <option value="{{ $v->id }}" @selected($veiculoEscolhido === (string) $v->id)>{{ $v->modelo }} · {{ $v->capacidade }} assentos</option>
                            @endif
                        @endforeach
                    </select>
                </label>
                <label>
                    Valor por passageiro (R$)
                    <input type="number" name="valor" value="{{ $campo('valor') }}" placeholder="25,00" min="0.01" max="99999.99" step="0.01" required>
                </label>

                @if ($veiculos->where('situacao', 'disponivel')->isEmpty() && ! $viagem)
                    <p class="nota largura-total">Você ainda não tem veículo disponível. Cadastre um em "Meus veículos" antes de criar uma viagem.</p>
                @endif
            </div>

            <div class="acoes">
                <a class="botao secundario" href="{{ route('viagens.index') }}">Voltar</a>
                <button type="submit" class="botao">{{ $botao }}</button>
            </div>
        </form>
    </section>
</section>
