<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    private const CATEGORIAS = ['B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'];

    public function index(Request $request)
    {
        $motorista = $request->user();

        return view('perfil.index', [
            'motorista' => $motorista,
            'totais' => [
                'veiculos' => $motorista->veiculos()->count(),
                'viagens' => $motorista->viagens()->count(),
                'passageiros' => $motorista->passageiros()->count(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $motorista = $request->user();

        $request->merge([
            'cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')),
            'telefone' => preg_replace('/\D/', '', (string) $request->input('telefone')),
            'cnh_numero' => preg_replace('/\D/', '', (string) $request->input('cnh_numero')),
        ]);

        $validador = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'cpf' => ['required', 'digits:11', Rule::unique('users', 'cpf')->ignore($motorista->id)],
            'telefone' => ['required', 'digits_between:10,11'],
            'cnh_numero' => ['nullable', 'digits_between:9,11'],
            'cnh_categoria' => ['required', Rule::in(self::CATEGORIAS)],
            'email' => ['nullable', 'email', 'max:120', Rule::unique('users', 'email')->ignore($motorista->id)],
            'cidade' => ['nullable', 'string', 'max:80'],
        ], [
            'name.required' => 'Informe seu nome completo.',
            'cpf.required' => 'Informe seu CPF.',
            'cpf.digits' => 'O CPF deve ter 11 números.',
            'cpf.unique' => 'Este CPF já está cadastrado em outra conta.',
            'telefone.required' => 'Informe seu telefone / WhatsApp.',
            'telefone.digits_between' => 'O telefone deve ter DDD, por exemplo (84) 99999-9999.',
            'cnh_numero.digits_between' => 'O número da CNH deve ter de 9 a 11 números.',
            'cnh_categoria.required' => 'Escolha a categoria da CNH.',
            'cnh_categoria.in' => 'Categoria da CNH inválida.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
        ]);

        if ($validador->fails()) {
            return redirect(route('perfil.index') . '#editar-perfil')
                ->withErrors($validador)
                ->withInput();
        }

        $d = $validador->validated();

        $motorista->update([
            'name' => $d['name'],
            'cpf' => $d['cpf'],
            'telefone' => $d['telefone'],
            'cnh_numero' => $d['cnh_numero'] ?? null,
            'cnh_categoria' => $d['cnh_categoria'],
            'email' => $d['email'] ?? $motorista->email,
            'cidade' => $d['cidade'] ?? null,
        ]);

        return redirect()->route('perfil.index')->with('status', 'Perfil atualizado com sucesso.');
    }
}
