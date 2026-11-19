<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#102A40">
    <title>Entrar • Rota Fácil</title>
    <link rel="icon" href="{{ asset('assets/favicon-rota-facil.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/desktop.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laravel.css') }}">
</head>

<body class="pagina-acesso pagina-login">
    <div class="aplicativo">
        <header class="topo-acesso">
            <img src="{{ asset('assets/logo-rota-facil.png') }}" alt="Rota Fácil">

            <section class="apresentacao-acesso">
                <small>NO SEU RITMO</small>
                <h2>Você escolhe a rota.<br>A gente organiza.</h2>
                <p>Defina destinos, horários e valores. Cada viagem com o transporte e os assentos que você precisa.</p>
            </section>
        </header>

        <main>
            <span class="selo selo-motorista"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="3" width="16" height="16" rx="3"></rect><path d="M4 10h16M12 3v7M7 19v2M17 19v2M7 14h1M16 14h1"></path></svg><span>ESPAÇO DO MOTORISTA</span></span>
            <h1>Recuperar acesso</h1>
            <p class="introducao">Informe seu e-mail cadastrado para receber um link de recuperação de senha.</p>
            
            @if (session('status'))
                <p class="sucesso-formulario">{{ session('status') }}</p>
            @endif

         <form method="POST" action="{{ route('senha.enviar') }}">
                    @csrf

                    @if ($errors->any())
                        <div class="erro-formulario">
                            @foreach ($errors->all() as $mensagem)
                                <p>{{ $mensagem }}</p>
                            @endforeach
                        </div>
                    @endif

                    <label>
                        E-mail cadastrado
                        <input type="email" name="email" value="{{ old('email') }}" placeholder="Digite seu e-mail" required autofocus>
                    </label>

                    <button type="submit" class="botao">Enviar link de recuperação</button>
                    <a href="{{ route('login') }}">Voltar ao login</a>
                </form>
            <p class="observacao-acesso">Primeira vez aqui?<a href="{{ route('cadastro') }}">Criar conta de motorista</a></p>
        </main>
    </div>
</body>
</html>
