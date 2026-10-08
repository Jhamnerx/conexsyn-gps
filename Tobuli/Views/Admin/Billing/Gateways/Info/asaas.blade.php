@extends('Admin.Layouts.modal')

@section('title')
    {{ trans('global.info') }}
@stop

@section('body')
    <p>
        The only accepted currency is <b>Brazilian Real (R$)</b>.
    </p>

    <br>

    <p>
        Configuration parameters are managed in <br>
        &emsp;<a href="https://asaas.com">https://asaas.com</a> (production) <br>
        &emsp;<a href="https://sandbox.asaas.com">https://sandbox.asaas.com</a> (sandbox)
    </p>

    <br>

    <p>
        To get the <b>API key</b> go to: Top menu (profile picture) > Integrações > Chave da API > Chaves da API > Gerar nova chave de API
    </p>

    <br>

    <p>
        Enable payments webhooks. Go to Integrações > Webhooks > Webhook para cobranças <br>
        Fill out and save:<br>
        &emsp; Webhook ativado? <b>Sim</b> <br>
        &emsp; URL: <b>^YOUR_WEBSITE^/payments/asaas/webhook</b> &ensp;(use <b>https://</b> link)<br>
        &emsp; Versão da API: <b>v3</b> <br>
        &emsp; Token de autenticação (opcional): Create password and also paste here to <b>Access token (webhook)</b> field<br>
        &emsp; Fila de sincronização ativada? <b>Sim</b>
    </p>

    <br>

    <p>
        Go to: Top menu (profile picture) > Minha Conta > Informações > Dados comerciais <br>
        Fill out and save:<br>
        &emsp;Site: <b>^YOUR_WEBSITE^</b><br>
    </p>

@stop

@section('footer')
    <button type="button" class="btn btn-default" data-dismiss="modal">{{ trans('global.close') }}</button>
@stop