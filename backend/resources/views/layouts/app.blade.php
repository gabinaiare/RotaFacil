<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#102A40">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo') • Rota Fácil</title>
    <link rel="icon" href="{{ asset('assets/favicon-rota-facil.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/desktop.css') }}">
    <link rel="stylesheet" href="{{ asset('css/passagens.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laravel.css') }}">
</head>

<body class="@yield('classe', 'pagina-index')">
    @php($usuario = auth()->user())

    <div class="aplicativo">

        <aside id="menu" class="menu-lateral">
            <div class="topo-menu">
                <img src="{{ asset('assets/logo-rota-facil.png') }}" alt="Rota Fácil">
                <a class="botao-fechar" href="#" aria-label="Fechar menu">×</a>
            </div>

            <nav>
                <a class="item-menu {{ request()->routeIs('dashboard') ? 'ativo' : '' }}" href="{{ route('dashboard') }}"><span class="icone icone-icons8-casa-24" aria-hidden="true"></span>Início</a>
                <a class="item-menu {{ request()->routeIs('viagens.*') ? 'ativo' : '' }}" href="{{ route('viagens.index') }}"><span class="icone icone-viajar-por" aria-hidden="true"></span>Minhas viagens</a>
                <a class="item-menu {{ request()->routeIs('veiculos.*') ? 'ativo' : '' }}" href="{{ route('veiculos.index') }}"><span class="icone icone-bus" aria-hidden="true"></span>Meus veículos</a>
                <a class="item-menu {{ request()->routeIs('passagens.*') ? 'ativo' : '' }}" href="{{ route('passagens.index') }}"><span class="icone icone-bilhete" aria-hidden="true"></span>Passagens</a>
                <a class="item-menu {{ request()->routeIs('encomendas.*') ? 'ativo' : '' }}" href="{{ route('encomendas.index') }}"><span class="icone icone-encomendas" aria-hidden="true"></span>Encomendas</a>
                <a class="item-menu {{ request()->routeIs('passageiros.*') ? 'ativo' : '' }}" href="{{ route('passageiros.index') }}"><span class="icone icone-cliente" aria-hidden="true"></span>Passageiros</a>
                <a class="item-menu {{ request()->routeIs('perfil.*') ? 'ativo' : '' }}" href="{{ route('perfil.index') }}"><span class="icone icone-do-utilizador" aria-hidden="true"></span>Meu perfil</a>
                <a class="item-menu {{ request()->routeIs('relatorios.*') ? 'ativo' : '' }}" href="{{ route('relatorios.index') }}"><span class="icone icone-relatorio-de-negocios-2" aria-hidden="true"></span>Relatórios</a>
            </nav>

            <footer>
                <div class="usuario">
                    <span class="avatar" @if ($usuario->foto_url) style="background:url('{{ $usuario->foto_url }}') center/cover;color:transparent" @endif>{{ $usuario->iniciais }}</span>
                    <div>
                        <strong>{{ $usuario->name }}</strong>
                        <small>Motorista independente</small>
                    </div>
                </div>
                <a class="sair-conta" href="#" onclick="event.preventDefault();document.getElementById('form-sair').submit();">Sair da conta</a>
            </footer>
        </aside>
        <a class="fundo-sobreposicao" href="#" aria-label="Fechar menu"></a>

        <header class="barra-superior">
            <a class="botao-menu" href="#menu" aria-label="Abrir menu"><span></span><span></span><span></span></a>
            <div>
                <small>Meu espaço</small>
                <strong>@yield('titulo')</strong>
            </div>

            <details class="conta-cabecalho">
                <summary class="avatar" aria-label="Abrir opções da conta de {{ $usuario->name }}" @if ($usuario->foto_url) style="background:url('{{ $usuario->foto_url }}') center/cover;color:transparent" @endif>{{ $usuario->iniciais }}</summary>

                <nav class="menu-conta" aria-label="Opções da conta">
                    <div class="identificacao-conta">
                        <strong>{{ $usuario->name }}</strong>
                        <small>Motorista independente</small>
                    </div>
                    <a href="{{ route('perfil.index') }}"><span class="icone icone-do-utilizador" aria-hidden="true"></span>Meu perfil</a>
                    <a class="sair-menu-conta" href="#" onclick="event.preventDefault();document.getElementById('form-sair').submit();"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 4H5v16h5M9 12h12m-4-4 4 4-4 4"></path></svg> Sair da conta</a>
                </nav>
            </details>
        </header>

        <main id="conteudo">
            @yield('conteudo')
            <footer class="rodape-pagina">Rota Fácil • Cada motorista, seu próprio caminho.</footer>
        </main>

        <nav class="menu-inferior" aria-label="Navegação principal">
            <a class="{{ request()->routeIs('dashboard') ? 'selecionado' : '' }}" href="{{ route('dashboard') }}"><span class="icone icone-icons8-casa-24" aria-hidden="true"></span><span>Início</span></a>
            <a class="{{ request()->routeIs('viagens.*') ? 'selecionado' : '' }}" href="{{ route('viagens.index') }}"><span class="icone icone-viajar-por" aria-hidden="true"></span><span>Viagens</span></a>
            <a class="{{ request()->routeIs('passagens.*') ? 'selecionado' : '' }}" href="{{ route('passagens.index') }}"><span class="icone icone-bilhete" aria-hidden="true"></span><span>Passagens</span></a>
            <a class="{{ request()->routeIs('passageiros.*') ? 'selecionado' : '' }}" href="{{ route('passageiros.index') }}"><span class="icone icone-cliente" aria-hidden="true"></span><span>Passageiros</span></a>
            <a href="#menu"><span class="icone icone-mais" aria-hidden="true"></span><span>Mais</span></a>
        </nav>

        @yield('janelas')
    </div>

    <form id="form-sair" method="POST" action="{{ route('logout') }}" hidden>
        @csrf
    </form>
</body>
</html>
