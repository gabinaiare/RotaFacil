<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public function mostrarLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $dados = $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Informe seu login ou e-mail.',
            'password.required' => 'Informe sua senha.',
        ]);

        // O motorista pode digitar o login ou o e-mail
        $campo = filter_var($dados['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'login';

        if (! Auth::attempt([$campo => $dados['login'], 'password' => $dados['password']])) {
            return back()
                ->withErrors(['login' => 'Login ou senha incorretos.'])
                ->onlyInput('login');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function mostrarCadastro()
    {
        return view('auth.cadastro');
    }

    public function cadastrar(Request $request)
    {
        // Tira máscara de CPF e telefone antes de validar
        $request->merge([
            'cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')),
            'telefone' => preg_replace('/\D/', '', (string) $request->input('telefone')),
            'email' => strtolower(trim((string) $request->input('email'))),
        ]);

        $dados = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'cpf' => ['required', 'digits:11', 'unique:users,cpf'],
            'telefone' => ['required', 'digits_between:10,11'],
            'email' => [
                'required', 'email', 'max:120',
                Rule::unique('users', 'email'),
                Rule::unique('users', 'login'),
            ],
            'cidade_estado' => ['nullable', 'string', 'max:80'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Informe seu nome completo.',
            'cpf.required' => 'Informe seu CPF.',
            'cpf.digits' => 'O CPF deve ter 11 números.',
            'cpf.unique' => 'Este CPF já está cadastrado.',
            'telefone.required' => 'Informe seu WhatsApp.',
            'telefone.digits_between' => 'Informe o WhatsApp com DDD, por exemplo (84) 99999-9999.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está cadastrado.',
            'password.required' => 'Crie uma senha.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ]);

        User::create([
            'name' => $dados['name'],
            'cpf' => $dados['cpf'],
            'telefone' => $dados['telefone'],
            'email' => $dados['email'],
            'login' => $dados['email'],
            'cidade_estado' => $dados['cidade_estado'] ?? null,
            'password' => $dados['password'],
        ]);

        return redirect()->route('login')
            ->with('status', 'Conta criada com sucesso! Entre com seu e-mail e senha.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}