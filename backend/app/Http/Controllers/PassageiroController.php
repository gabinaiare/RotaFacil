<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PassageiroController extends Controller
{
    public function index(Request $request)
    {
        $passageiros = $request->user()->passageiros()
            ->withCount(['passagens as total_passagens' => fn ($q) => $q->where('situacao', 'ativa')])
            ->orderBy('nome')
            ->get();

        return view('passageiros.index', compact('passageiros'));
    }

    public function store(Request $request)
    {
        $validador = $this->validar($request);

        if ($validador->fails()) {
            return redirect(route('passageiros.index') . '#novo-cliente')
                ->withErrors($validador)
                ->withInput();
        }

        $request->user()->passageiros()->create($validador->validated());

        return redirect()->route('passageiros.index')
            ->with('status', 'Passageiro cadastrado com sucesso.');
    }

    public function update(Request $request, int $id)
    {
        // Só encontra passageiros do próprio motorista; os de outros dão 404
        $passageiro = $request->user()->passageiros()->findOrFail($id);

        $validador = $this->validar($request, $passageiro->id);

        if ($validador->fails()) {
            return redirect(route('passageiros.index') . '#editar-cliente-' . $passageiro->id)
                ->withErrors($validador, 'passageiro_' . $passageiro->id)
                ->withInput()
                ->with('editando', $passageiro->id);
        }

        $passageiro->update($validador->validated());

        return redirect()->route('passageiros.index')
            ->with('status', 'Passageiro atualizado com sucesso.');
    }

    private function validar(Request $request, ?int $ignorarId = null)
    {
        $cpf = preg_replace('/\D/', '', (string) $request->input('cpf'));
        $email = strtolower(trim((string) $request->input('email')));
        $endereco = trim((string) $request->input('endereco'));

        // Campos opcionais em branco viram NULL (e não texto vazio)
        $request->merge([
            'cpf' => $cpf !== '' ? $cpf : null,
            'telefone' => preg_replace('/\D/', '', (string) $request->input('telefone')),
            'email' => $email !== '' ? $email : null,
            'endereco' => $endereco !== '' ? $endereco : null,
        ]);

        $motoristaId = $request->user()->id;
        $unicoDoMotorista = fn (string $coluna) => Rule::unique('passageiros', $coluna)
            ->where(fn ($q) => $q->where('user_id', $motoristaId))
            ->ignore($ignorarId);

        return Validator::make($request->all(), [
            'nome' => ['required', 'string', 'max:120'],
            'telefone' => ['required', 'digits_between:10,11', $unicoDoMotorista('telefone')],
            'cpf' => ['nullable', 'digits:11', $unicoDoMotorista('cpf')],
            'email' => ['nullable', 'email', 'max:120'],
            'endereco' => ['nullable', 'string', 'max:200'],
        ], [
            'nome.required' => 'Informe o nome completo do passageiro.',
            'telefone.required' => 'Informe o WhatsApp com DDD.',
            'telefone.digits_between' => 'Informe o WhatsApp com DDD, por exemplo (84) 99999-9999.',
            'telefone.unique' => 'Já existe um passageiro com este telefone.',
            'cpf.digits' => 'O CPF deve ter 11 números.',
            'cpf.unique' => 'Já existe um passageiro com este CPF.',
            'email.email' => 'Informe um e-mail válido.',
        ]);
    }
}
