@extends('layouts.app')

@section('titulo', 'Meu perfil')
@section('classe', 'pagina-perfil')

@section('conteudo')
    @php
        $iniciais = collect(preg_split('/\s+/', trim($motorista->name)))
            ->filter()->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->take(2)->implode('');

        $d = preg_replace('/\D/', '', (string) $motorista->cpf);
        $cpf = strlen($d) === 11 ? sprintf('%s.%s.%s-%s', substr($d, 0, 3), substr($d, 3, 3), substr($d, 6, 3), substr($d, 9)) : null;

        $t = preg_replace('/\D/', '', (string) $motorista->telefone);
        $telefone = match (strlen($t)) {
            11 => sprintf('(%s) %s-%s', substr($t, 0, 2), substr($t, 2, 5), substr($t, 7)),
            10 => sprintf('(%s) %s-%s', substr($t, 0, 2), substr($t, 2, 4), substr($t, 6)),
            default => $motorista->telefone,
        };
    @endphp

    <div class="titulo-pagina">
        <div>
            <h1>Meu perfil</h1>
            <p class="introducao">Seus dados de motorista e informações de contato.</p>
        </div>
        <a class="botao" href="#editar-perfil">+ Editar perfil</a>
    </div>

    @if (session('status'))
        <p class="sucesso-formulario">{{ session('status') }}</p>
    @endif

    <section class="cartao identidade-perfil">
        <span class="avatar avatar-grande">{{ $iniciais }}</span>
        <h2>{{ $motorista->name }}</h2>
        <p class="texto-secundario">Motorista independente</p>
        <span class="selo">Meu espaço individual</span>
        @if ($motorista->cidade)
            <p class="texto-secundario">{{ $motorista->cidade }}</p>
        @endif
    </section>

    <section class="cartao dados-perfil">
        <header class="linha">
            <h2>Dados pessoais</h2>
            <a href="#editar-perfil">Editar</a>
        </header>
        <dl>
            <div><dt>CPF</dt><dd>{{ $cpf ?? 'Não informado' }}</dd></div>
            <div><dt>CNH / categoria</dt><dd>{{ $motorista->cnh_numero ?: 'Não informado' }} • {{ $motorista->cnh_categoria ?: '—' }}</dd></div>
            <div><dt>Nome completo</dt><dd>{{ $motorista->name }}</dd></div>
            <div><dt>Telefone / WhatsApp</dt><dd>{{ $telefone ?: 'Não informado' }}</dd></div>
            <div><dt>E-mail</dt><dd>{{ $motorista->email ?: 'Não informado' }}</dd></div>
            <div><dt>Cidade e estado</dt><dd>{{ $motorista->cidade ?: 'Não informado' }}</dd></div>
        </dl>

        <footer class="numeros-perfil">
            <span><strong>{{ $totais['veiculos'] }}</strong> veículos</span>
            <span><strong>{{ $totais['viagens'] }}</strong> viagens</span>
            <span><strong>{{ $totais['passageiros'] }}</strong> passageiros</span>
        </footer>
    </section>
@endsection

@section('janelas')
    @php
        $valor = fn (string $campo, $atual) => old($campo, $atual);
        $catAtual = $valor('cnh_categoria', $motorista->cnh_categoria ?: 'B');
    @endphp

    <section class="janela" id="editar-perfil" aria-label="Painel: editar perfil">
        <a class="fundo-janela" href="{{ route('perfil.index') }}" aria-label="Fechar painel"></a>

        <section class="painel" role="dialog" aria-modal="true" aria-labelledby="editar-perfil-titulo" tabindex="-1">
            <header class="painel-cabecalho">
                <div>
                    <span class="marca-painel">ROTA FÁCIL</span>
                    <h2 id="editar-perfil-titulo">Editar meu perfil</h2>
                </div>
                <a class="fechar-painel" href="{{ route('perfil.index') }}" aria-label="Fechar">×</a>
            </header>

            <form class="formulario" method="POST" action="{{ route('perfil.update') }}">
                @csrf
                @method('PUT')

                <div class="grade-formulario">
                    @if ($errors->any())
                        <div class="erro-formulario largura-total">
                            @foreach ($errors->all() as $mensagem)
                                <p>{{ $mensagem }}</p>
                            @endforeach
                        </div>
                    @endif

                    <label>
                        Nome completo
                        <input type="text" name="name" value="{{ $valor('name', $motorista->name) }}" maxlength="120" required>
                    </label>
                    <label>
                        CPF
                        <input type="text" name="cpf" value="{{ $valor('cpf', $cpf) }}" placeholder="000.000.000-00" required>
                    </label>
                    <label>
                        Telefone / WhatsApp
                        <input type="tel" name="telefone" value="{{ $valor('telefone', $telefone) }}" placeholder="(84) 99999-9999" required>
                    </label>
                    <label>
                        Número da CNH
                        <input type="text" name="cnh_numero" value="{{ $valor('cnh_numero', $motorista->cnh_numero) }}">
                    </label>
                    <label>
                        Categoria da CNH
                        <select name="cnh_categoria">
                            @foreach (['B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'] as $c)
                                <option value="{{ $c }}" @selected($catAtual === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        E-mail
                        <small>opcional</small>
                        <input type="email" name="email" value="{{ $valor('email', $motorista->email) }}">
                    </label>
                    <label>
                        Cidade e estado
                        <small>opcional</small>
                        <input type="text" name="cidade" value="{{ $valor('cidade', $motorista->cidade) }}" placeholder="São Miguel, RN">
                    </label>
                </div>

                <div class="acoes">
                    <a class="botao secundario" href="{{ route('perfil.index') }}">Cancelar</a>
                    <button type="submit" class="botao">Salvar perfil</button>
                </div>
            </form>
        </section>
    </section>
@endsection
