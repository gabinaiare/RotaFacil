@extends('layouts.app')

@section('titulo', 'Meu perfil')
@section('classe', 'pagina-perfil')

@section('conteudo')
    @php
        $estiloFoto = $u->foto_url ? "background:url('{$u->foto_url}') center/cover;color:transparent" : null;
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
        <span class="avatar avatar-grande" @if ($estiloFoto) style="{{ $estiloFoto }}" @endif>{{ $u->iniciais }}</span>
        <h2>{{ $u->name }}</h2>
        <p class="texto-secundario">Motorista independente</p>
        <span class="selo">Meu espaço individual</span>
        @if ($u->cidade_estado)
            <p class="texto-secundario">{{ $u->cidade_estado }}</p>
        @endif
        <a class="link-foto" href="#editar-perfil">{{ $u->foto ? 'Trocar foto do perfil' : 'Adicionar foto do perfil' }}</a>
    </section>

    <section class="cartao dados-perfil">
        <header class="linha">
            <h2>Dados pessoais</h2>
            <a href="#editar-perfil">Editar</a>
        </header>
        <dl>
            <div>
                <dt>CPF</dt>
                <dd>{{ $u->cpf_formatado ?: 'Não informado' }}</dd>
            </div>
            <div>
                <dt>CNH / categoria</dt>
                <dd>{{ $u->numero_cnh ?: 'Não informado' }} • {{ $u->categoria_cnh ?: '—' }}</dd>
            </div>
            <div>
                <dt>Nome completo</dt>
                <dd>{{ $u->name }}</dd>
            </div>
            <div>
                <dt>Telefone / WhatsApp</dt>
                <dd>{{ $u->telefone_formatado ?: 'Não informado' }}</dd>
            </div>
            <div>
                <dt>E-mail</dt>
                <dd>{{ $u->email }}</dd>
            </div>
            <div>
                <dt>Cidade e estado</dt>
                <dd>{{ $u->cidade_estado ?: 'Não informado' }}</dd>
            </div>
        </dl>

        <footer class="numeros-perfil">
            <span><strong>{{ $totais['veiculos'] }}</strong> {{ $totais['veiculos'] === 1 ? 'veículo' : 'veículos' }}</span>
            <span><strong>{{ $totais['viagens'] }}</strong> {{ $totais['viagens'] === 1 ? 'viagem' : 'viagens' }}</span>
            <span><strong>{{ $totais['passageiros'] }}</strong> {{ $totais['passageiros'] === 1 ? 'passageiro' : 'passageiros' }}</span>
        </footer>
    </section>
@endsection

@section('janelas')
    @php
        $usarOld = $errors->any();
        $campo = fn (string $nome, $padrao) => $usarOld ? old($nome, $padrao) : $padrao;
        $categoria = $campo('categoria_cnh', $u->categoria_cnh);
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

            <form class="formulario" method="POST" action="{{ route('perfil.update') }}" enctype="multipart/form-data">
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

                    <section class="foto-editor largura-total">
                        <span class="avatar avatar-grande" aria-hidden="true" @if ($estiloFoto) style="{{ $estiloFoto }}" @endif>{{ $u->iniciais }}</span>
                        <div>
                            <h2>Foto do perfil</h2>
                            <p class="texto-secundario">Adicione uma foto para identificar seu espaço.</p>
                            <label class="escolher-foto">
                                Escolher foto
                                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" aria-describedby="foto-ajuda">
                            </label>
                            @if ($u->foto)
                                <label class="opcao">
                                    <input type="checkbox" name="remover_foto" value="1" @checked(old('remover_foto'))>
                                    Remover a foto atual
                                </label>
                            @endif
                        </div>
                        <p id="foto-ajuda" class="nota largura-total">JPG, PNG ou WebP, com até 2 MB.</p>
                    </section>

                    <label>
                        Nome completo
                        <input type="text" name="name" value="{{ $campo('name', $u->name) }}" maxlength="120" required>
                    </label>
                    <label>
                        CPF
                        <input type="text" name="cpf" value="{{ $campo('cpf', $u->cpf_formatado) }}" placeholder="000.000.000-00" inputmode="numeric" required>
                    </label>
                    <label>
                        Telefone / WhatsApp
                        <input type="tel" name="telefone" value="{{ $campo('telefone', $u->telefone_formatado) }}" placeholder="(84) 99999-9999" required>
                    </label>
                    <label>
                        Número da CNH
                        <small>opcional</small>
                        <input type="text" name="numero_cnh" value="{{ $campo('numero_cnh', $u->numero_cnh) }}" inputmode="numeric">
                    </label>
                    <label>
                        Categoria da CNH
                        <small>opcional</small>
                        <select name="categoria_cnh">
                            <option value="">Não informada</option>
                            @foreach ($categorias as $c)
                                <option value="{{ $c }}" @selected($categoria === $c)>{{ $c }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>
                        E-mail
                        <input type="email" name="email" value="{{ $campo('email', $u->email) }}" maxlength="120" required>
                    </label>
                    <label>
                        Cidade e estado
                        <small>opcional</small>
                        <input type="text" name="cidade_estado" value="{{ $campo('cidade_estado', $u->cidade_estado) }}" placeholder="São Miguel, RN" maxlength="80">
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
