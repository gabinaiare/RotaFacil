<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class RecuperarAcessoController extends Controller
{
    /** Tela "Recuperar acesso": pede o e-mail */
    public function mostrarPedido()
    {
        return view('auth.recuperar-acesso');
    }

    /** Envia o link. A resposta é sempre a mesma, para não revelar quais e-mails existem. */
    public function enviarLink(Request $request)
    {
        $dados = $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'Informe o e-mail cadastrado.',
            'email.email' => 'Informe um e-mail válido.',
        ]);

        $resultado = Password::sendResetLink(['email' => $dados['email']]);

        if ($resultado === Password::RESET_THROTTLED) {
            return back()->withInput()->withErrors([
                'email' => 'Aguarde um minuto antes de pedir outro link.',
            ]);
        }

        return back()->with('status', 'Se este e-mail estiver cadastrado, enviamos um link para redefinir a senha. Ele vale por 60 minutos.');
    }

    /** Tela para digitar a nova senha (abre pelo link do e-mail) */
    public function mostrarRedefinicao(Request $request, string $token)
    {
        return view('auth.redefinir-senha', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function redefinir(Request $request)
    {
        $dados = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'email.required' => 'Informe o e-mail cadastrado.',
            'password.required' => 'Informe a nova senha.',
            'password.min' => 'A senha deve ter no mínimo 8 caracteres.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ]);

        $resultado = Password::reset(
            $dados,
            function ($usuario, string $senha) {
                $usuario->forceFill([
                    'password' => Hash::make($senha),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($usuario));
            }
        );

        if ($resultado !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'Este link é inválido ou expirou. Peça um novo link.',
            ]);
        }

        return redirect()->route('login')->with('status', 'Senha alterada com sucesso. Entre com a nova senha.');
    }
}
