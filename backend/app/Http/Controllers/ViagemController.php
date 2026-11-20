<?php

namespace App\Http\Controllers;

use App\Models\Viagem;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ViagemController extends Controller
{
    public function index(Request $request)
    {
        $motorista = $request->user();
        $busca = trim((string) $request->query('busca'));
        $data = (string) $request->query('data');
        $dataValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) === 1;

        // Parte sempre do motorista logado: cada um vê só as próprias viagens
        $viagens = $motorista->viagens()
            ->with([
                'veiculo',
                'passagens' => fn ($q) => $q->where('situacao', 'ativa')
                    ->with(['passageiro', 'pagamentos'])
                    ->orderBy('assento'),
            ])
            ->when($busca !== '', fn ($q) => $q->where(
                fn ($q) => $q->where('origem', 'like', "%{$busca}%")->orWhere('destino', 'like', "%{$busca}%")
            ))
            ->when($dataValida, fn ($q) => $q->whereDate('saida', $data))
            ->orderByRaw("CASE situacao WHEN 'agendada' THEN 0 ELSE 1 END")
            ->orderBy('saida')
            ->get();

        $veiculos = $motorista->veiculos()->orderBy('modelo')->get();

        return view('viagens.index', [
            'viagens' => $viagens,
            'veiculos' => $veiculos,
            'busca' => $busca,
            'data' => $dataValida ? $data : '',
        ]);
    }

    public function store(Request $request)
    {
        $validador = $this->validar($request);

        if ($validador->fails()) {
            return redirect(route('viagens.index') . '#nova-viagem')
                ->withErrors($validador)
                ->withInput();
        }

        $request->user()->viagens()->create(
            $this->montar($request, $validador->validated()) + [
                'codigo' => 'V-' . Str::upper(Str::random(6)),
                'situacao' => 'agendada',
            ]
        );

        return redirect()->route('viagens.index')
            ->with('status', 'Viagem cadastrada com sucesso.');
    }

    public function update(Request $request, int $id)
    {
        $viagem = $request->user()->viagens()->findOrFail($id);

        if ($viagem->situacao !== 'agendada') {
            return redirect()->route('viagens.index')
                ->with('erro', 'Só é possível editar viagens agendadas.');
        }

        $validador = $this->validar($request, $viagem);

        if ($validador->fails()) {
            return redirect(route('viagens.index') . '#editar-viagem-' . $viagem->id)
                ->withErrors($validador, 'viagem_' . $viagem->id)
                ->withInput()
                ->with('editando', $viagem->id);
        }

        $viagem->update($this->montar($request, $validador->validated()));

        return redirect()->route('viagens.index')
            ->with('status', 'Viagem atualizada com sucesso.');
    }

    public function cancelar(Request $request, int $id)
    {
        $viagem = $request->user()->viagens()->findOrFail($id);

        if ($viagem->situacao !== 'agendada') {
            return redirect()->route('viagens.index')
                ->with('erro', 'Só é possível cancelar viagens agendadas.');
        }

        $dados = $request->validate([
            'motivo' => ['nullable', 'string', 'max:150'],
        ]);

        DB::transaction(function () use ($viagem, $dados) {
            // As passagens ativas são canceladas (o histórico fica) e os assentos liberados
            $viagem->passagens()->where('situacao', 'ativa')->update([
                'situacao' => 'cancelada',
                'data_cancelamento' => now(),
                'motivo_cancelamento' => 'Viagem cancelada' . (! empty($dados['motivo']) ? ': ' . $dados['motivo'] : ''),
            ]);

            $viagem->update(['situacao' => 'cancelada']);
        });

        return redirect()->route('viagens.index')
            ->with('status', 'Viagem cancelada. As passagens foram canceladas e os assentos liberados.');
    }

    public function concluir(Request $request, int $id)
    {
        $viagem = $request->user()->viagens()->findOrFail($id);

        if ($viagem->situacao !== 'agendada') {
            return redirect()->route('viagens.index')
                ->with('erro', 'Só é possível concluir viagens agendadas.');
        }

        $viagem->update(['situacao' => 'concluida']);

        return redirect()->route('viagens.index')
            ->with('status', 'Viagem marcada como concluída.');
    }

    private function validar(Request $request, ?Viagem $viagem = null)
    {
        $motorista = $request->user();

        $request->merge([
            'origem' => trim((string) $request->input('origem')),
            'destino' => trim((string) $request->input('destino')),
            'valor' => str_replace(',', '.', trim((string) $request->input('valor'))),
        ]);

        $validador = Validator::make($request->all(), [
            'origem' => ['required', 'string', 'max:80'],
            'destino' => ['required', 'string', 'max:80', 'different:origem'],
            'data_saida' => ['required', 'date_format:Y-m-d'],
            'horario_saida' => ['required', 'date_format:H:i'],
            'veiculo_id' => [
                'required', 'integer',
                Rule::exists('veiculos', 'id')->where(fn ($q) => $q->where('user_id', $motorista->id)),
            ],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
        ], [
            'origem.required' => 'Informe a origem.',
            'destino.required' => 'Informe o destino.',
            'destino.different' => 'A origem e o destino devem ser diferentes.',
            'data_saida.required' => 'Informe a data de saída.',
            'data_saida.date_format' => 'Data de saída inválida.',
            'horario_saida.required' => 'Informe o horário de saída.',
            'horario_saida.date_format' => 'Horário de saída inválido.',
            'veiculo_id.required' => 'Selecione o veículo.',
            'veiculo_id.exists' => 'Selecione um veículo seu.',
            'valor.required' => 'Informe o valor por passageiro.',
            'valor.numeric' => 'O valor deve ser um número, por exemplo 25,00.',
            'valor.min' => 'O valor deve ser maior que zero.',
        ]);

        // Regras de negócio que dependem do banco (UC-06: RN2, RN3 e RN4)
        $validador->after(function ($v) use ($request, $motorista, $viagem) {
            if ($v->errors()->isNotEmpty()) {
                return;
            }

            $saida = Carbon::createFromFormat('Y-m-d H:i', $request->input('data_saida') . ' ' . $request->input('horario_saida'));
            $veiculo = $motorista->veiculos()->find($request->input('veiculo_id'));

            if (! $viagem && $saida->isPast()) {
                $v->errors()->add('data_saida', 'A saída deve ser uma data e um horário futuros.');
            }

            $mudouVeiculo = ! $viagem || (int) $viagem->veiculo_id !== (int) $veiculo->id;
            if ($mudouVeiculo && $veiculo->situacao !== 'disponivel') {
                $v->errors()->add('veiculo_id', 'Este veículo está indisponível.');
            }

            $conflito = Viagem::where('veiculo_id', $veiculo->id)
                ->where('saida', $saida)
                ->where('situacao', '!=', 'cancelada')
                ->when($viagem, fn ($q) => $q->whereKeyNot($viagem->id))
                ->exists();

            if ($conflito) {
                $v->errors()->add('veiculo_id', 'Este veículo já tem uma viagem neste horário.');
            }

            if ($viagem) {
                $ativas = $viagem->passagens()->where('situacao', 'ativa');
                $necessario = max((int) (clone $ativas)->max('assento'), (int) (clone $ativas)->count());

                if ($veiculo->capacidade < $necessario) {
                    $v->errors()->add('veiculo_id', "Este veículo tem {$veiculo->capacidade} lugares, mas a viagem já tem passagens vendidas até o assento {$necessario}.");
                }
            }
        });

        return $validador;
    }

    private function montar(Request $request, array $dados): array
    {
        $veiculo = $request->user()->veiculos()->findOrFail($dados['veiculo_id']);

        return [
            'veiculo_id' => $veiculo->id,
            'origem' => $dados['origem'],
            'destino' => $dados['destino'],
            'saida' => Carbon::createFromFormat('Y-m-d H:i', $dados['data_saida'] . ' ' . $dados['horario_saida']),
            'valor' => $dados['valor'],
            'capacidade' => $veiculo->capacidade, // cópia da capacidade do veículo (RN4)
        ];
    }
}
