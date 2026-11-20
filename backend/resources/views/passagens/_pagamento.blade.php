@php
    $usarOld = session('editando') == $p->id;
    $erros = $errors->getBag('pagamento_' . $p->id);
    $forma = $usarOld ? old('forma', 'pix') : 'pix';
    $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
@endphp
<section class="janela pagamento-passagem" id="pagamento-{{ $p->id }}" aria-label="Painel: pagamento">
    <a class="fundo-janela" href="{{ $fechar }}" aria-label="Fechar painel"></a>
    <section class="painel" role="dialog" aria-modal="true" tabindex="-1">
        <header class="painel-cabecalho">
            <div>
                <span class="marca-painel">ROTA FÁCIL</span>
                <h2>Registrar pagamento</h2>
            </div>
            <a class="fechar-painel" href="{{ $fechar }}" aria-label="Fechar">×</a>
        </header>

        <form class="formulario" method="POST" action="{{ route('passagens.receber', $p->id) }}">
            @csrf
            <input type="hidden" name="retorno" value="{{ $retorno ?? '' }}">
            <section class="resumo-recebimento">
                <p>{{ $p->passageiro->nome }} · Assento {{ sprintf('%02d', $p->assento) }}</p>
                <strong>{{ $dinheiro($p->saldo) }}</strong>
                <small>Saldo a receber</small>
            </section>

            <div class="grade-formulario">
                @if ($erros->any())
                    <div class="erro-formulario largura-total">
                        @foreach ($erros->all() as $mensagem)<p>{{ $mensagem }}</p>@endforeach
                    </div>
                @endif
                <label class="largura-total">
                    Forma de pagamento
                    <select name="forma">
                        <option value="pix" @selected($forma === 'pix')>Pix</option>
                        <option value="dinheiro" @selected($forma === 'dinheiro')>Dinheiro</option>
                        <option value="cartao" @selected($forma === 'cartao')>Cartão</option>
                    </select>
                </label>
                <label class="largura-total">
                    Valor a pagar agora (R$)
                    <input type="number" name="valor" value="{{ $usarOld ? old('valor') : number_format($p->saldo, 2, '.', '') }}" min="0.01" max="{{ number_format($p->saldo, 2, '.', '') }}" step="0.01" required>
                </label>
            </div>
            <p class="nota">Você pode receber só parte do saldo. Este registro não realiza cobranças por Pix ou cartão.</p>
            <div class="acoes">
                <a class="botao secundario" href="{{ $fechar }}">Cancelar</a>
                <button class="botao" type="submit">Confirmar pagamento</button>
            </div>
        </form>
    </section>
</section>
