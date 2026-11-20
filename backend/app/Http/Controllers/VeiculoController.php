<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class VeiculoController extends Controller
{
    public function index(Request $request)
    {
        $veiculos = $request->user()->veiculos()->orderBy('modelo')->get();

        return view('veiculos.index', compact('veiculos'));
    }

    public function store(Request $request)
    {
        $validador = $this->validar($request);

        if ($validador->fails()) {
            return redirect(route('veiculos.index') . '#novo-veiculo')
                ->withErrors($validador)
                ->withInput();
        }

        $request->user()->veiculos()->create($this->montar($validador->validated()));

        return redirect()->route('veiculos.index')
            ->with('status', 'Veículo cadastrado com sucesso.');
    }

    public function update(Request $request, int $id)
    {
        // Só encontra veículos do próprio motorista; os de outros dão 404
        $veiculo = $request->user()->veiculos()->findOrFail($id);

        $validador = $this->validar($request, $veiculo->id);

        if ($validador->fails()) {
            return redirect(route('veiculos.index') . '#editar-veiculo-' . $veiculo->id)
                ->withErrors($validador, 'veiculo_' . $veiculo->id)
                ->withInput()
                ->with('editando', $veiculo->id);
        }

        $veiculo->update($this->montar($validador->validated()));

        return redirect()->route('veiculos.index')
            ->with('status', 'Veículo atualizado com sucesso.');
    }

    private function validar(Request $request, ?int $ignorarId = null)
    {
        // Placa sem hífen, espaço ou minúsculas
        $request->merge([
            'placa' => strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->input('placa'))),
        ]);

        $motoristaId = $request->user()->id;

        return Validator::make($request->all(), [
            'modelo' => ['required', 'string', 'max:80'],
            'tipo' => ['required', Rule::in(['van', 'onibus'])],
            // Aceita o padrão antigo (ABC1234) e o Mercosul (ABC1D23)
            'placa' => [
                'required',
                'regex:/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/',
                Rule::unique('veiculos', 'placa')
                    ->where(fn ($q) => $q->where('user_id', $motoristaId))
                    ->ignore($ignorarId),
            ],
            'capacidade' => ['required', 'integer', 'min:1', 'max:60'],
            'situacao' => ['required', Rule::in(['disponivel', 'indisponivel'])],
        ], [
            'modelo.required' => 'Informe o modelo do veículo.',
            'tipo.in' => 'Escolha van ou ônibus.',
            'placa.required' => 'Informe a placa.',
            'placa.regex' => 'Placa inválida. Use o formato ABC1D23 ou ABC1234.',
            'placa.unique' => 'Você já cadastrou um veículo com esta placa.',
            'capacidade.required' => 'Informe a capacidade de passageiros.',
            'capacidade.integer' => 'A capacidade deve ser um número inteiro.',
            'capacidade.min' => 'A capacidade deve ser de pelo menos 1 passageiro.',
            'capacidade.max' => 'A capacidade máxima é de 60 passageiros.',
            'situacao.in' => 'Escolha uma situação válida.',
        ]);
    }

    private function montar(array $dados): array
    {
        // A placa serve como identificação única do veículo (RN1 do UC-04)
        $dados['identificacao'] = $dados['placa'];

        return $dados;
    }
}
