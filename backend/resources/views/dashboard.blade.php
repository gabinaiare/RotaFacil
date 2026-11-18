@extends('layouts.app')

@section('titulo', 'Visão geral')

@section('conteudo')
    <h1>Olá, {{ auth()->user()->primeiro_nome }}</h1>
    <p class="introducao">Seu transporte, seus clientes e suas contas em um só lugar.</p>
@endsection
