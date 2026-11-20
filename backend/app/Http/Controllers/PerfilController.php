<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PerfilController extends Controller
{
    private const CATEGORIAS = ['A', 'B', 'C', 'D', 'E', 'AB', 'AC', 'AD', 'AE'];

    public function index(Request $request)
    {
        $u = $request->user();

        return view('perfil.index', [
            'u' => $u,
            'categorias' => self::CATEGORIAS,
            'totais' => [
                'veiculos' => $u->veiculos()->count(),
                'viagens' => $u->viagens()->count(),
                'passageiros' => $u->passageiros()->count(),
            ],
        ]);
    }

    /** UC-05: o motorista altera somente os próprios dados (RN2) */
    public function update(Request $request)
    {
        $u = $request->user();

        $nulo = fn (?string $v) => ($v = trim((string) $v)) !== '' ? $v : null;

        $request->merge([
            'cpf' => preg_replace('/\D/', '', (string) $request->input('cpf')),
            'telefone' => preg_replace('/\D/', '', (string) $request->input('telefone')),
            'email' => strtolower(trim((string) $request->input('email'))),
            'numero_cnh' => $nulo(preg_replace('/\D/', '', (string) $request->input('numero_cnh'))),
            'categoria_cnh' => $nulo(strtoupper((string) $request->input('categoria_cnh'))),
            'cidade_estado' => $nulo($request->input('cidade_estado')),
        ]);

        $validador = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'cpf' => ['required', 'digits:11', Rule::unique('users', 'cpf')->ignore($u->id)],
            'telefone' => ['required', 'digits_between:10,11'],
            'email' => [
                'required', 'email', 'max:120',
                Rule::unique('users', 'email')->ignore($u->id),
                Rule::unique('users', 'login')->ignore($u->id),
            ],
            'cidade_estado' => ['nullable', 'string', 'max:80'],
            'numero_cnh' => ['nullable', 'digits_between:9,11'],
            'categoria_cnh' => ['nullable', Rule::in(self::CATEGORIAS)],
            'foto' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Informe seu nome completo.',
            'cpf.required' => 'Informe seu CPF.',
            'cpf.digits' => 'O CPF deve ter 11 números.',
            'cpf.unique' => 'Este CPF já está cadastrado em outra conta.',
            'telefone.required' => 'Informe seu WhatsApp.',
            'telefone.digits_between' => 'Informe o WhatsApp com DDD, por exemplo (84) 99999-9999.',
            'email.required' => 'Informe seu e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso em outra conta.',
            'numero_cnh.digits_between' => 'A CNH deve ter de 9 a 11 números.',
            'categoria_cnh.in' => 'Escolha uma categoria válida.',
            'foto.mimes' => 'A foto deve ser JPG, PNG ou WebP.',
            'foto.max' => 'A foto deve ter no máximo 2 MB.',
            'foto.uploaded' => 'Não foi possível enviar a foto. Ela pode ser grande demais.',
        ]);

        if ($validador->fails()) {
            return redirect(route('perfil.index') . '#editar-perfil')
                ->withErrors($validador)
                ->withInput();
        }

        $d = $validador->validated();

        // Se o login era o e-mail (padrão do cadastro), ele acompanha o novo e-mail
        $loginSegueEmail = $u->login === $u->email;

        $u->fill([
            'name' => $d['name'],
            'cpf' => $d['cpf'],
            'telefone' => $d['telefone'],
            'email' => $d['email'],
            'cidade_estado' => $d['cidade_estado'] ?? null,
            'numero_cnh' => $d['numero_cnh'] ?? null,
            'categoria_cnh' => $d['categoria_cnh'] ?? null,
        ]);

        if ($loginSegueEmail) {
            $u->login = $d['email'];
        }

        if ($request->hasFile('foto')) {
            if ($u->foto) {
                Storage::disk('public')->delete($u->foto);
            }
            $u->foto = $request->file('foto')->store('fotos', 'public');
        } elseif ($request->boolean('remover_foto') && $u->foto) {
            Storage::disk('public')->delete($u->foto);
            $u->foto = null;
        }

        $u->save();

        return redirect()->route('perfil.index')->with('status', 'Perfil atualizado com sucesso.');
    }
}
