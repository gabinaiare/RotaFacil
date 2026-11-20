<?php

namespace App\Http\Controllers;

use App\Models\Encomenda;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class EncomendaController extends Controller
{
    private const FORMAS = ['dinheiro', 'pix', 'cartao'];

    public function index(Request $request)
    {
        $motorista = $request->user();
        $busca = trim((string) $request->query('busca'));
        $situacao = (string) $request->query('situacao');

        if (! array_key_exists($situacao, Encomenda::SITUACOES)) {
            $situacao = '';
        }

        // Parte sempre do motorista logado: cada um vê só as próprias encomendas
        $encomendas = $motorista->encomendas()
            ->with(['pagamentos', 'viagem', 'movimentacoes'])
            ->when($busca !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('codigo', 'like', "%{$busca}%")
                ->orWhere('descricao', 'like', "%{$busca}%")
                ->orWhere('remetente_nome', 'like', "%{$busca}%")
                ->orWhere('destinatario_nome', 'like', "%{$busca}%")))
            ->when($situacao !== '', fn ($q) => $q->where('situacao', $situacao))
            ->orderByRaw("CASE WHEN situacao = 'entregue' THEN 1 ELSE 0 END")
            ->orderByDesc('id')
            ->get();

        return view('encomendas.index', [
            'encomendas' => $encomendas,
            'busca' => $busca,
            'situacao' => $situacao,
            'emAcompanhamento' => $motorista->encomendas()->where('situacao', '!=', 'entregue')->count(),
            'entregues' => $motorista->encomendas()->where('situacao', 'entregue')->count(),
            'viagens' => $motorista->viagens()->where('situacao', 'agendada')->orderBy('saida')->get(),
            'passageiros' => $motorista->passageiros()->orderBy('nome')->get(),
        ]);
    }

    /** UC-10: cadastrar encomenda */
    public function store(Request $request)
    {
        $voltar = $this->retorno($request, route('encomendas.index'));
        $validador = $this->validar($request);

        if ($validador->fails()) {
            return redirect($voltar . '#nova-encomenda')
                ->withErrors($validador)
                ->withInput();
        }

        $d = $validador->validated();

        $encomenda = DB::transaction(function () use ($request, $d) {
            $encomenda = $request->user()->encomendas()->create($d + [
                'codigo' => $this->gerarCodigo(),
                'situacao' => 'cadastrada',
            ]);

            $encomenda->movimentacoes()->create([
                'situacao_anterior' => null,
                'nova_situacao' => 'cadastrada',
                'observacao' => 'Encomenda cadastrada.',
            ]);

            $this->sincronizarPagamento($encomenda);

            return $encomenda;
        });

        return redirect($voltar)
            ->with('status', "Encomenda cadastrada. Código: {$encomenda->codigo}");
    }

    public function update(Request $request, int $id)
    {
        $encomenda = $request->user()->encomendas()->with('pagamentos')->findOrFail($id);

        if ($encomenda->situacao === 'entregue') {
            return redirect()->route('encomendas.index')
                ->with('erro', 'Uma encomenda já entregue não pode ser editada.');
        }

        $validador = $this->validar($request, $encomenda);

        if ($validador->fails()) {
            return redirect(route('encomendas.index') . '#editar-encomenda-' . $encomenda->id)
                ->withErrors($validador, 'encomenda_' . $encomenda->id)
                ->withInput()
                ->with('editando', $encomenda->id);
        }

        DB::transaction(function () use ($encomenda, $validador) {
            $encomenda->update($validador->validated());
            $this->sincronizarPagamento($encomenda);
        });

        return redirect()->route('encomendas.index')
            ->with('status', 'Encomenda atualizada com sucesso.');
    }

    /** UC-12 (atualizar situação) e UC-13 (confirmar entrega = situação "entregue") */
    public function atualizarStatus(Request $request, int $id)
    {
        $validador = Validator::make($request->all(), [
            'situacao' => ['required', Rule::in(array_keys(Encomenda::SITUACOES))],
            'observacao' => ['nullable', 'string', 'max:200'],
        ]);

        if ($validador->fails()) {
            return redirect()->route('encomendas.index')->with('erro', 'Escolha uma situação válida.');
        }

        $d = $validador->validated();
        $ordem = array_keys(Encomenda::SITUACOES);

        $erro = DB::transaction(function () use ($request, $id, $d, $ordem) {
            $encomenda = $request->user()->encomendas()->lockForUpdate()->findOrFail($id);

            // Não permite voltar nem repetir a situação (RN3 do UC-12 e RN3 do UC-13)
            if (array_search($d['situacao'], $ordem) <= array_search($encomenda->situacao, $ordem)) {
                return $encomenda->situacao === 'entregue'
                    ? 'Esta encomenda já foi entregue.'
                    : 'Escolha uma situação posterior à atual.';
            }

            $anterior = $encomenda->situacao;

            $encomenda->update([
                'situacao' => $d['situacao'],
                'data_entrega' => $d['situacao'] === 'entregue' ? now() : $encomenda->data_entrega,
            ]);

            $encomenda->movimentacoes()->create([
                'situacao_anterior' => $anterior,
                'nova_situacao' => $d['situacao'],
                'observacao' => $d['observacao'] ?? null,
            ]);

            return null;
        });

        if ($erro) {
            return redirect()->route('encomendas.index')->with('erro', $erro);
        }

        return redirect()->route('encomendas.index')->with(
            'status',
            $d['situacao'] === 'entregue' ? 'Entrega confirmada.' : 'Situação atualizada.'
        );
    }

    /** Receber (total ou parcialmente) o valor do transporte */
    public function receber(Request $request, int $id)
    {
        $encomenda = $request->user()->encomendas()->with('pagamentos')->findOrFail($id);

        if ($encomenda->saldo <= 0) {
            return redirect()->route('encomendas.index')->with('erro', 'Esta encomenda não tem valor a receber.');
        }

        $request->merge(['valor' => trim(str_replace(',', '.', (string) $request->input('valor')))]);

        $validador = Validator::make($request->all(), [
            'forma' => ['required', Rule::in(self::FORMAS)],
            'valor' => ['required', 'numeric', 'min:0.01', 'max:' . $encomenda->saldo],
        ], [
            'forma.required' => 'Escolha a forma de pagamento.',
            'forma.in' => 'Forma de pagamento inválida.',
            'valor.required' => 'Informe o valor recebido.',
            'valor.numeric' => 'O valor deve ser um número.',
            'valor.min' => 'O valor deve ser maior que zero.',
            'valor.max' => 'O valor não pode ser maior que o saldo a receber.',
        ]);

        if ($validador->fails()) {
            return redirect(route('encomendas.index') . '#pagamento-encomenda-' . $encomenda->id)
                ->withErrors($validador, 'pagamento_' . $encomenda->id)
                ->withInput()
                ->with('editando', $encomenda->id);
        }

        $d = $validador->validated();

        DB::transaction(function () use ($encomenda, $d) {
            Encomenda::whereKey($encomenda->id)->lockForUpdate()->first();

            $encomenda->pagamentos()->create([
                'forma' => $d['forma'],
                'situacao' => 'pago',
                'valor' => round((float) $d['valor'], 2),
                'data_pagamento' => now(),
            ]);

            $this->sincronizarPagamento($encomenda);
        });

        return redirect()->route('encomendas.index')->with('status', 'Pagamento registrado.');
    }

    public function comprovante(Request $request, int $id)
    {
        $encomenda = $request->user()->encomendas()->with(['pagamentos', 'viagem'])->findOrFail($id);

        return view('encomendas.comprovante', [
            'encomenda' => $encomenda,
            'motorista' => $request->user(),
        ]);
    }

    private function validar(Request $request, ?Encomenda $encomenda = null)
    {
        $motorista = $request->user();
        $valor = trim(str_replace(',', '.', (string) $request->input('valor')));

        $request->merge([
            'remetente_telefone' => preg_replace('/\D/', '', (string) $request->input('remetente_telefone')),
            'destinatario_telefone' => preg_replace('/\D/', '', (string) $request->input('destinatario_telefone')),
            'valor' => $valor !== '' ? $valor : null,
            'viagem_id' => $request->filled('viagem_id') ? $request->input('viagem_id') : null,
        ]);

        $validador = Validator::make($request->all(), [
            'remetente_nome' => ['required', 'string', 'max:120'],
            'remetente_telefone' => ['required', 'digits_between:10,11'],
            'destinatario_nome' => ['required', 'string', 'max:120'],
            'destinatario_telefone' => ['required', 'digits_between:10,11'],
            'descricao' => ['required', 'string', 'max:255'],
            'valor' => ['nullable', 'numeric', 'min:0', 'max:99999.99'],
            'viagem_id' => [
                'nullable', 'integer',
                Rule::exists('viagens', 'id')->where(fn ($q) => $q->where('user_id', $motorista->id)),
            ],
            'origem' => ['required', 'string', 'max:80'],
            'destino' => ['required', 'string', 'max:80'],
        ], [
            'remetente_nome.required' => 'Informe o nome de quem envia (De).',
            'remetente_telefone.required' => 'Informe o telefone de quem envia (De).',
            'remetente_telefone.digits_between' => 'O telefone de quem envia deve ter DDD, por exemplo (84) 99999-9999.',
            'destinatario_nome.required' => 'Informe o nome de quem recebe (Para).',
            'destinatario_telefone.required' => 'Informe o telefone de quem recebe (Para).',
            'destinatario_telefone.digits_between' => 'O telefone de quem recebe deve ter DDD, por exemplo (84) 99999-9999.',
            'descricao.required' => 'Descreva a encomenda.',
            'valor.numeric' => 'O valor deve ser um número, por exemplo 20,00.',
            'valor.min' => 'O valor não pode ser negativo.',
            'viagem_id.exists' => 'Selecione uma viagem sua.',
            'origem.required' => 'Informe a origem.',
            'destino.required' => 'Informe o destino.',
        ]);

        // Não dá para baixar o valor abaixo do que já foi recebido
        $validador->after(function ($v) use ($request, $encomenda) {
            if ($encomenda && $v->errors()->isEmpty()) {
                $recebido = $encomenda->recebido;

                if ((float) ($request->input('valor') ?? 0) + 0.001 < $recebido) {
                    $v->errors()->add('valor', 'O valor não pode ser menor que o já recebido (R$ ' . number_format($recebido, 2, ',', '.') . ').');
                }
            }
        });

        return $validador;
    }

    /** Mantém uma única linha "pendente" com o que ainda falta receber */
    private function sincronizarPagamento(Encomenda $encomenda): void
    {
        $encomenda->load('pagamentos');

        $falta = round((float) ($encomenda->valor ?? 0) - $encomenda->recebido, 2);
        $pendente = $encomenda->pagamentos->firstWhere('situacao', 'pendente');

        if ($falta > 0) {
            if ($pendente) {
                $pendente->update(['valor' => $falta]);
            } else {
                $encomenda->pagamentos()->create(['forma' => 'fiado', 'situacao' => 'pendente', 'valor' => $falta]);
            }
        } elseif ($pendente) {
            $pendente->delete();
        }
    }

    private function gerarCodigo(): string
    {
        do {
            $codigo = 'ENC-' . random_int(100000, 999999);
        } while (Encomenda::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
