@php
    $idSel = (string) old('viagem_id', $vendaveis->first()?->id);
    $fecharVenda = route('dashboard');
@endphp

<section class="janela" id="vender-passagem" aria-label="Painel: vender passagem">
    <a class="fundo-janela" href="{{ $fecharVenda }}" aria-label="Fechar painel"></a>

    <section class="painel" role="dialog" aria-modal="true" aria-labelledby="vender-passagem-titulo" tabindex="-1">
        <header class="painel-cabecalho">
            <div>
                <span class="marca-painel">ROTA FÁCIL</span>
                <h2 id="vender-passagem-titulo">Registrar venda de passagem</h2>
            </div>
            <a class="fechar-painel" href="{{ $fecharVenda }}" aria-label="Fechar">×</a>
        </header>

        @if ($vendaveis->isEmpty())
            <p class="nota">Você não tem viagens agendadas. Cadastre uma em Minhas viagens.</p>
        @else
            <form class="formulario" method="POST" action="{{ route('passagens.store') }}">
                @csrf
                <input type="hidden" name="retorno" value="inicio">

                <div class="grade-formulario">
                    @if ($errors->any())
                        <div class="erro-formulario largura-total">
                            @foreach ($errors->all() as $mensagem)
                                <p>{{ $mensagem }}</p>
                            @endforeach
                        </div>
                    @endif

                    <label class="largura-total">
                        Viagem
                        <select name="viagem_id" id="venda-viagem">
                            @foreach ($vendaveis as $v)
                                <option value="{{ $v->id }}" data-valor="{{ number_format($v->valor, 2, '.', '') }}" @selected($idSel === (string) $v->id)>{{ $v->origem }} → {{ $v->destino }} · {{ $v->saida->format('d/m/Y') }} · {{ $v->saida->format('H:i') }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="largura-total">
                        Passageiro
                        <select name="passageiro_id" required>
                            <option value="">Selecione o passageiro</option>
                            @foreach ($passageiros as $pa)
                                <option value="{{ $pa->id }}" @selected((string) old('passageiro_id') === (string) $pa->id)>{{ $pa->nome }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="largura-total">
                        @foreach ($vendaveis as $v)
                            @php($ocupados = $v->passagens->pluck('assento')->map(fn ($a) => (int) $a)->all())
                            <fieldset class="campo-assentos" data-mapa="{{ $v->id }}">
                                <legend class="sr-only">Mapa de assentos</legend>
                                <p class="frente">FRENTE DO VEÍCULO</p>
                                <div class="assentos">
                                    @for ($n = 1; $n <= $v->capacidade; $n++)
                                        <label class="assento">
                                            <input aria-label="Assento {{ sprintf('%02d', $n) }}" type="radio" name="assento" value="{{ $n }}"
                                                data-ocupado="{{ in_array($n, $ocupados) ? 1 : 0 }}"
                                                @disabled(in_array($n, $ocupados))
                                                @checked($idSel === (string) $v->id && (string) old('assento') === (string) $n)>
                                            <span>{{ sprintf('%02d', $n) }}</span>
                                        </label>
                                    @endfor
                                </div>
                            </fieldset>
                        @endforeach
                        <div class="legenda">
                            <span><i class="livre"></i> Livre</span>
                            <span><i class="ocupado"></i> Ocupado</span>
                            <span><i class="selecionado"></i> Selecionado</span>
                        </div>
                    </div>

                    <label>
                        Valor da passagem (R$)
                        <input type="number" id="venda-valor" name="valor" value="{{ old('valor', number_format($vendaveis->firstWhere('id', (int) $idSel)?->valor ?? $vendaveis->first()->valor, 2, '.', '')) }}" min="0.01" step="0.01" required>
                    </label>
                    <label>
                        Situação do pagamento
                        <select name="situacao_pagamento" id="situacao-pagamento">
                            <option value="pago" @selected(old('situacao_pagamento', 'pago') === 'pago')>Pago</option>
                            <option value="fiado" @selected(old('situacao_pagamento') === 'fiado')>Fiado • ainda não recebido</option>
                            <option value="parcial" @selected(old('situacao_pagamento') === 'parcial')>Pago parcialmente</option>
                        </select>
                    </label>
                    <label class="forma-pagamento-principal" id="bloco-forma">
                        Forma de pagamento
                        <select name="forma_pagamento">
                            <option value="dinheiro" @selected(old('forma_pagamento') === 'dinheiro')>Dinheiro</option>
                            <option value="pix" @selected(old('forma_pagamento', 'pix') === 'pix')>Pix</option>
                            <option value="cartao" @selected(old('forma_pagamento') === 'cartao')>Cartão</option>
                        </select>
                    </label>
                    <div class="valor-parcial largura-total" id="bloco-parcial">
                        <label>
                            Valor já recebido (R$)
                            <input type="number" name="valor_recebido" value="{{ old('valor_recebido') }}" min="0.01" step="0.01" placeholder="Ex.: 10,00">
                        </label>
                        <small>O restante fica a receber (fiado).</small>
                    </div>
                </div>

                <div class="acoes">
                    <a class="botao secundario" href="{{ $fecharVenda }}">Cancelar</a>
                    <button type="submit" class="botao">Registrar passagem</button>
                </div>
            </form>

            <script>
                (function () {
                    var viagem = document.getElementById('venda-viagem');
                    var valor = document.getElementById('venda-valor');
                    var situacao = document.getElementById('situacao-pagamento');
                    var parcial = document.getElementById('bloco-parcial');
                    var forma = document.getElementById('bloco-forma');

                    function mostrarMapa(trocou) {
                        document.querySelectorAll('[data-mapa]').forEach(function (mapa) {
                            var ativo = mapa.dataset.mapa === viagem.value;
                            mapa.hidden = !ativo;
                            mapa.querySelectorAll('input[type=radio]').forEach(function (r) {
                                r.disabled = !ativo || r.dataset.ocupado === '1';
                                if (!ativo || trocou) { r.checked = false; }
                            });
                        });
                        if (trocou) { valor.value = viagem.selectedOptions[0].dataset.valor; }
                    }

                    function pagamento() {
                        parcial.style.display = situacao.value === 'parcial' ? 'block' : 'none';
                        forma.style.display = situacao.value === 'fiado' ? 'none' : '';
                    }

                    viagem.addEventListener('change', function () { mostrarMapa(true); });
                    situacao.addEventListener('change', pagamento);
                    mostrarMapa(false);
                    pagamento();
                })();
            </script>
        @endif
    </section>
</section>
