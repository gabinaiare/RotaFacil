<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#102A40">
    <title>Criar conta • Rota Fácil</title>
    <link rel="icon" href="{{ asset('assets/favicon-rota-facil.png') }}">
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('css/desktop.css') }}">
    <link rel="stylesheet" href="{{ asset('css/laravel.css') }}">
</head>

<body class="pagina-acesso pagina-cadastro">
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
            <h1>Seu espaço começa aqui</h1>
            <p class="introducao">Crie sua conta para gerenciar seus veículos, clientes e recebimentos.</p>

            <form method="POST" action="{{ route('cadastro') }}">
                @csrf

                <label>
                    Nome completo
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Digite seu nome" autocomplete="name" required>
                </label>
                @error('name') <p class="erro-formulario">{{ $message }}</p> @enderror

                <label>
                    CPF
                    <input type="text" name="cpf" value="{{ old('cpf') }}" placeholder="000.000.000-00" inputmode="numeric" required>
                </label>
                @error('cpf') <p class="erro-formulario">{{ $message }}</p> @enderror

                <label>
                    Seu WhatsApp
                    <input type="tel" name="telefone" value="{{ old('telefone') }}" placeholder="(84) 99999-9999" autocomplete="tel" required>
                </label>
                @error('telefone') <p class="erro-formulario">{{ $message }}</p> @enderror

                <label>
                    E-mail
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="seuemail@gmail.com" autocomplete="email" required>
                </label>
                @error('email') <p class="erro-formulario">{{ $message }}</p> @enderror

                <label>
                    Cidade e estado
                    <input type="text" name="cidade_estado" value="{{ old('cidade_estado') }}" placeholder="São Miguel, RN">
                </label>
                @error('cidade_estado') <p class="erro-formulario">{{ $message }}</p> @enderror

                <label>
                    Senha · mínimo 8 caracteres
                    <span class="campo-senha">
                        <input type="password" name="password" placeholder="Digite sua senha" autocomplete="new-password" required>
                        <span class="olhinho-senha" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
                    </span>
                </label>
                @error('password') <p class="erro-formulario">{{ $message }}</p> @enderror

                <label>
                    Confirme sua senha
                    <span class="campo-senha">
                        <input type="password" name="password_confirmation" placeholder="Digite sua senha novamente" autocomplete="new-password" required>
                        <span class="olhinho-senha" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg></span>
                    </span>
                </label>

                <button class="botao" type="submit">Criar meu espaço</button>
            </form>
            <p class="observacao-acesso">Já tem conta? <a href="{{ route('login') }}">Entrar</a></p>
        </main>
    </div>
</body>
</html>
