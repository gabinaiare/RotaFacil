@php
    $dinheiro = fn ($v) => 'R$ ' . number_format($v, 2, ',', '.');
    $pendentes = $p->passagens->filter(fn ($x) => $x->saldo > 0);
@endphp

<section class="janela" id="fiado-{{ $p->id }}" aria-label="Painel: fiado de {{ $p->nome }}">
    <a class="fundo-janela" href="{{ route('passageiros.index') }}" aria-label="Fechar painel"></a>
    <section class="painel" role="dialog" aria-modal="true" tabindex="-1">
        <header class="painel-cabecalho">
            <div>
                <span class="marca-painel">ROTA FÁCIL</span>
                <h2>Fiado de {{ $p->nome }}</h2>
            </div>
            <a class="fechar-painel" href="{{ route('passageiros.index') }}" aria-label="Fechar">×</a>
        </header>

        <section class="resumo-recebimento">
            <strong>{{ $dinheiro($p->fiado) }}</strong>
            <small>Total a receber</small>
        </section>

        <div class="lista-fiado">
            @forelse ($pendentes as $x)
                <div class="item-fiado">
                    <div>
                        <strong>{{ $x->viagem->origem }} → {{ $x->viagem->destino }}</strong>
                        <small>{{ $x->viagem->saida->format('d/m/Y • H:i') }} · Assento {{ sprintf('%02d', $x->assento) }}</small>
                        <span class="amarelo">Falta {{ $dinheiro($x->saldo) }}</span>
                    </div>
                    <a class="botao" href="#pagamento-{{ $x->id }}">Receber</a>
                </div>
            @empty
                <p class="nota">Nenhuma passagem com saldo a receber.</p>
            @endforelse
        </div>

        <div class="acoes">
            <a class="botao secundario" href="{{ route('passageiros.index') }}">Fechar</a>
        </div>
    </section>
</section>

@foreach ($pendentes as $x)
    @php($x->setRelation('passageiro', $p))
    @include('passagens._pagamento', ['p' => $x, 'fechar' => route('passageiros.index'), 'retorno' => 'passageiros'])
@endforeach