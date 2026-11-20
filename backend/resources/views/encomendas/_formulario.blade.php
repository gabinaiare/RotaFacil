@php
    $erros = $errors->getBag($bag);

    $base = $encomenda ? [
        'remetente_nome' => $encomenda->remetente_nome,
        'remetente_telefone' => $encomenda->remetente_telefone_formatado,
        'destinatario_nome' => $encomenda->destinatario_nome,
        'destinatario_telefone' => $encomenda->destinatario_telefone_formatado,
        'descricao' => $encomenda->descricao,
        'valor' => $encomenda->valor !== null ? number_format($encomenda->valor, 2, '.', '') : '',
        'viagem_id' => $encomenda->viagem_id,
        'origem' => $encomenda->origem,
        'destino' => $encomenda->destino,
    ] : [];

    // O que o usuário digitou (se houve erro) ou o que está salvo
    $campo = fn (string $nome) => $usarOld ? old($nome, $base[$nome] ?? '') : ($base[$nome] ?? '');
    $viagemEscolhida = (string) $campo('viagem_id');
@endphp

<section class="janela" id="{{ $idPainel }}" aria-label="Painel: {{ $titulo }}">
    <a class="fundo-janela" href="{{ $fechar ?? route('encomendas.index') }}" aria-label="Fechar painel"></a>

    <section class="painel" role="dialog" aria-modal="true" aria-labelledby="{{ $idPainel }}-titulo" tabindex="-1">

        <header class="painel-cabecalho">
            <div>
                <span class="marca-painel">ROTA FÁCIL</span>
                <h2 id="{{ $idPainel }}-titulo">{{ $titulo }}</h2>
            </div>
            <a class="fechar-painel" href="{{ $fechar ?? route('encomendas.index') }}" aria-label="Fechar">×</a>
        </header>

        <form class="formulario" method="POST" action="{{ $acao }}">
            @csrf
            <input type="hidden" name="retorno" value="{{ $retorno ?? '' }}">
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
                    De: passageiro cadastrado
                    <small>opcional</small>
                    <select name="remetente_cadastrado"
                            onchange="var o=this.selectedOptions[0]; if(o && o.value){ this.form.remetente_nome.value=o.dataset.nome; this.form.remetente_telefone.value=o.dataset.telefone; }">
                        <option value="">Selecione para preencher</option>
                        @foreach ($passageiros as $pa)
                            <option value="{{ $pa->id }}" data-nome="{{ $pa->nome }}" data-telefone="{{ $pa->telefone_formatado }}">{{ $pa->nome }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    De: nome
                    <input type="text" name="remetente_nome" value="{{ $campo('remetente_nome') }}" maxlength="120" required>
                </label>

                <label>
                    De: telefone
                    <input type="tel" name="remetente_telefone" value="{{ $campo('remetente_telefone') }}" placeholder="(84) 99999-9999" required>
                </label>

                <label>
                    Para: nome
                    <input type="text" name="destinatario_nome" value="{{ $campo('destinatario_nome') }}" maxlength="120" required>
                </label>

                <label>
                    Para: telefone
                    <input type="tel" name="destinatario_telefone" value="{{ $campo('destinatario_telefone') }}" placeholder="(84) 99999-9999" required>
                </label>

                <label>
                    Descrição da encomenda
                    <input type="text" name="descricao" value="{{ $campo('descricao') }}" placeholder="Caixa de roupas" maxlength="255" required>
                </label>

                <label>
                    Valor do transporte (R$)
                    <small>opcional</small>
                    <input type="number" name="valor" value="{{ $campo('valor') }}" placeholder="20,00" min="0" max="99999.99" step="0.01">
                </label>

                <label>
                    Viagem
                    <small>pode ser definida depois</small>
                    <select name="viagem_id">
                        <option value="">Selecionar depois</option>
                        @foreach ($viagens as $v)
                            <option value="{{ $v->id }}" @selected($viagemEscolhida === (string) $v->id)>{{ $v->origem }} → {{ $v->destino }} • {{ $v->saida->format('d/m/Y') }} • {{ $v->saida->format('H:i') }}</option>
                        @endforeach
                        @if ($encomenda && $encomenda->viagem && ! $viagens->contains('id', $encomenda->viagem_id))
                            <option value="{{ $encomenda->viagem_id }}" @selected($viagemEscolhida === (string) $encomenda->viagem_id)>{{ $encomenda->viagem->origem }} → {{ $encomenda->viagem->destino }} • {{ $encomenda->viagem->saida->format('d/m/Y') }}</option>
                        @endif
                    </select>
                </label>

                <label>
                    Origem da encomenda
                    <input type="text" name="origem" value="{{ $campo('origem') }}" placeholder="São Miguel" maxlength="80" required>
                </label>

                <label>
                    Destino da encomenda
                    <input type="text" name="destino" value="{{ $campo('destino') }}" placeholder="Mossoró" maxlength="80" required>
                </label>

                <p class="nota largura-total">O código é gerado ao salvar. A encomenda começa como Cadastrada e as mudanças de situação ficam no histórico.</p>
            </div>

            <div class="acoes">
                <a class="botao secundario" href="{{ $fechar ?? route('encomendas.index') }}">Cancelar</a>
                <button type="submit" class="botao">{{ $botao }}</button>
            </div>
        </form>
    </section>
</section>
