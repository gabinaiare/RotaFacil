<?php

namespace App\Http\Controllers;

use App\Models\Passagem;
use App\Models\Viagem;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PassagemController extends Controller
{
    private const FORMAS = ['dinheiro', 'pix', 'cartao'];

    public function index(Request $request)
    {
        $motorista = $request->user();

        $viagens = $motorista->viagens()->where('situacao', 'agendada')->orderBy('saida')->get();
        $viagem = $viagens->firstWhere('id', (int) $request->query('viagem')) ?? $viagens->first();

        $passagens = collect();
        $passageiros = collect();

        if ($viagem) {
            $viagem->load('veiculo');

            $passagens = $viagem->passagens()
                ->with(['passageiro', 'pagamentos'])
                ->orderByRaw("CASE situacao WHEN 'ativa' THEN 0 ELSE 1 END")
                ->orderBy('assento')
                ->get();

            $passageiros = $motorista->passageiros()->orderBy('nome')->get();
        }

        $ativas = $passagens->where('situacao', 'ativa');

        return view('passagens.index', [
            'viagens' => $viagens,
            'viagem' => $viagem,
            'passagens' => $passagens,
            'passageiros' => $passageiros,
            'ocupados' => $ativas->pluck('assento')->map(fn ($a) => (int) $a)->all(),
            'contadores' => [
                'ativas' => $ativas->count(),
                'pagas' => $ativas->filter(fn ($p) => $p->saldo <= 0)->count(),
                'a_receber' => $ativas->filter(fn ($p) => $p->saldo > 0)->count(),
                'canceladas' => $passagens->where('situacao', 'cancelada')->count(),
            ],
        ]);
    }

    /** UC-08: registrar venda de passagem */
    public function store(Request $request)
    {
        $motorista = $request->user();

        $recebido = trim(str_replace(',', '.', (string) $request->input('valor_recebido')));
        $request->merge([
            'valor' => trim(str_replace(',', '.', (string) $request->input('valor'))),
            'valor_recebido' => $recebido !== '' ? $recebido : null,
        ]);

        $situacao = $request->input('situacao_pagamento');

        $validador = Validator::make($request->all(), [
            'viagem_id' => [
                'required', 'integer',
                Rule::exists('viagens', 'id')->where(fn ($q) => $q->where('user_id', $motorista->id)->where('situacao', 'agendada')),
            ],
            'passageiro_id' => [
                'required', 'integer',
                Rule::exists('passageiros', 'id')->where(fn ($q) => $q->where('user_id', $motorista->id)),
            ],
            'assento' => ['required', 'integer', 'min:1'],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'situacao_pagamento' => ['required', Rule::in(['fiado', 'pago', 'parcial'])],
            'forma_pagamento' => [
                Rule::requiredIf(fn () => in_array($situacao, ['pago', 'parcial'], true)),
                'nullable', Rule::in(self::FORMAS),
            ],
            'valor_recebido' => [
                Rule::requiredIf(fn () => $situacao === 'parcial'),
                'nullable', 'numeric', 'min:0.01',
            ],
        ], [
            'viagem_id.exists' => 'Viagem indisponível para venda.',
            'passageiro_id.required' => 'Selecione o passageiro.',
            'passageiro_id.exists' => 'Selecione um passageiro seu.',
            'assento.required' => 'Escolha um assento no mapa.',
            'valor.required' => 'Informe o valor da passagem.',
            'valor.numeric' => 'O valor deve ser um número, por exemplo 25,00.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'situacao_pagamento.in' => 'Escolha a situação do pagamento.',
            'forma_pagamento.required' => 'Escolha a forma de pagamento.',
            'forma_pagamento.in' => 'Forma de pagamento inválida.',
            'valor_recebido.required' => 'Informe o valor já recebido.',
            'valor_recebido.numeric' => 'O valor recebido deve ser um número.',
        ]);

        $validador->after(function ($v) use ($request) {
            if ($v->errors()->isEmpty()
                && $request->input('situacao_pagamento') === 'parcial'
                && (float) $request->input('valor_recebido') >= (float) $request->input('valor')) {
                $v->errors()->add('valor_recebido', 'O valor recebido deve ser menor que o valor da passagem. Se já recebeu tudo, escolha "Pago".');
            }
        });

        // Volta para a tela de onde veio (Início ou Passagens)
        $voltar = $this->retorno(
            $request,
            route('passagens.index', ['viagem' => $request->input('viagem_id')])
        ) . '#vender-passagem';

        if ($validador->fails()) {
            return redirect($voltar)->withErrors($validador)->withInput();
        }

        $d = $validador->validated();

        try {
            $passagem = DB::transaction(function () use ($motorista, $d) {
                // Trava a viagem: duas vendas simultâneas esperam uma pela outra
                $viagem = Viagem::where('id', $d['viagem_id'])
                    ->where('user_id', $motorista->id)
                    ->where('situacao', 'agendada')
                    ->lockForUpdate()
                    ->firstOrFail();

                if ((int) $d['assento'] > $viagem->capacidade) {
                    throw ValidationException::withMessages([
                        'assento' => "Este veículo só tem {$viagem->capacidade} assentos.",
                    ]);
                }

                $ocupado = $viagem->passagens()
                    ->where('situacao', 'ativa')
                    ->where('assento', $d['assento'])
                    ->exists();

                if ($ocupado) {
                    throw ValidationException::withMessages([
                        'assento' => 'Este assento já foi vendido. Escolha outro.',
                    ]);
                }

                $valor = round((float) $d['valor'], 2);

                $passagem = $viagem->passagens()->create([
                    'passageiro_id' => $d['passageiro_id'],
                    'codigo' => 'P-' . Str::upper(Str::random(6)),
                    'assento' => $d['assento'],
                    'valor' => $valor,
                ]);

                match ($d['situacao_pagamento']) {
                    'pago' => $passagem->pagamentos()->create([
                        'forma' => $d['forma_pagamento'], 'situacao' => 'pago',
                        'valor' => $valor, 'data_pagamento' => now(),
                    ]),
                    'fiado' => $passagem->pagamentos()->create([
                        'forma' => 'fiado', 'situacao' => 'pendente', 'valor' => $valor,
                    ]),
                    'parcial' => $this->registrarParcial($passagem, $d['forma_pagamento'], $valor, round((float) $d['valor_recebido'], 2)),
                };

                return $passagem;
            });
        } catch (ValidationException $e) {
            return redirect($voltar)->withErrors($e->errors())->withInput();
        } catch (QueryException $e) {
            // Rede de segurança: o índice único do banco recusou o assento duplicado
            if ($e->getCode() === '23000') {
                return redirect($voltar)
                    ->withErrors(['assento' => 'Este assento acabou de ser vendido. Escolha outro.'])
                    ->withInput();
            }

            throw $e;
        }

        $destino = $this->retorno(
            $request,
            route('passagens.index', ['viagem' => $passagem->viagem_id])
        );

        return redirect($destino)
            ->with('status', 'Passagem registrada: assento ' . sprintf('%02d', $passagem->assento) . '.')
            ->with('comprovante', $passagem->id);
    }

    /** UC-09: cancelar passagem (o histórico é mantido e o assento fica livre) */
    public function cancelar(Request $request, int $id)
    {
        $passagem = $this->doMotorista($request, $id);

        $voltar = $this->retorno(
            $request,
            route('passagens.index', ['viagem' => $passagem->viagem_id])
        );

        if ($passagem->situacao !== 'ativa' || $passagem->viagem->situacao !== 'agendada') {
            return redirect($voltar)->with('erro', 'Esta passagem não pode ser cancelada.');
        }

        $dados = $request->validate([
            'motivo' => ['nullable', 'string', 'max:200'],
        ]);

        $passagem->update([
            'situacao' => 'cancelada',
            'data_cancelamento' => now(),
            'motivo_cancelamento' => $dados['motivo'] ?? null,
        ]);

        return redirect($voltar)
            ->with('status', 'Passagem cancelada. O assento ' . sprintf('%02d', $passagem->assento) . ' está livre.');
    }

    /** Receber (total ou parcialmente) o valor que ficou fiado */
    public function receber(Request $request, int $id)
    {
        $passagem = $this->doMotorista($request, $id);
        $passagem->load('pagamentos');

        // Volta para a tela de onde veio (Passagens ou Passageiros)
        $voltar = $this->retorno(
            $request,
            route('passagens.index', ['viagem' => $passagem->viagem_id])
        );

        if ($passagem->situacao !== 'ativa' || $passagem->saldo <= 0) {
            return redirect($voltar)->with('erro', 'Esta passagem não tem valor a receber.');
        }

        $request->merge(['valor' => trim(str_replace(',', '.', (string) $request->input('valor')))]);

        $validador = Validator::make($request->all(), [
            'forma' => ['required', Rule::in(self::FORMAS)],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:' . $passagem->saldo],
        ], [
            'forma.required' => 'Escolha a forma de pagamento.',
            'forma.in' => 'Forma de pagamento inválida.',
            'valor.required' => 'Informe o valor recebido.',
            'valor.numeric' => 'O valor deve ser um número.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'valor.max' => 'O valor não pode ser maior que o saldo a receber.',
        ]);

        if ($validador->fails()) {
            return redirect($voltar . '#pagamento-' . $passagem->id)
                ->withErrors($validador, 'pagamento_' . $passagem->id)
                ->withInput()
                ->with('editando', $passagem->id);
        }

        $d = $validador->validated();

        DB::transaction(function () use ($passagem, $d) {
            $pendente = $passagem->pagamentos()->where('situacao', 'pendente')->lockForUpdate()->firstOrFail();

            $valor = min(round((float) $d['valor'], 2), (float) $pendente->valor);

            $passagem->pagamentos()->create([
                'forma' => $d['forma'], 'situacao' => 'pago',
                'valor' => $valor, 'data_pagamento' => now(),
            ]);

            $restante = round((float) $pendente->valor - $valor, 2);

            if ($restante <= 0) {
                $pendente->delete();
            } else {
                $pendente->update(['valor' => $restante]);
            }
        });

        return redirect($voltar)->with('status', 'Pagamento registrado.');
    }

    public function comprovante(Request $request, int $id)
    {
        $passagem = $this->doMotorista($request, $id);
        $passagem->load(['viagem.veiculo', 'passageiro', 'pagamentos']);

        return view('passagens.comprovante', [
            'passagem' => $passagem,
            'motorista' => $request->user(),
        ]);
    }

    private function registrarParcial(Passagem $passagem, string $forma, float $total, float $entrada): void
    {
        $passagem->pagamentos()->create([
            'forma' => $forma, 'situacao' => 'pago',
            'valor' => $entrada, 'data_pagamento' => now(),
        ]);

        $passagem->pagamentos()->create([
            'forma' => 'fiado', 'situacao' => 'pendente',
            'valor' => round($total - $entrada, 2),
        ]);
    }

    /** Só encontra passagens de viagens do próprio motorista; as de outros dão 404 */
    private function doMotorista(Request $request, int $id): Passagem
    {
        return Passagem::whereHas('viagem', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('viagem')
            ->findOrFail($id);
    }
}