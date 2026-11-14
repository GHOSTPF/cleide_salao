@extends('site.layout')

@section('titulo', 'Pedido recebido — ' . \App\Support\Setting::get('nome_salao'))

@section('conteudo')
<main id="conteudo" class="mx-auto max-w-lg px-4 py-10 sm:py-16"
      x-data="{
          link: @js($linkWhatsapp),
          beacon: @js(route('agendamento.whatsapp', $ag->token_publico)),
          token: @js(csrf_token()),
          registrar: function () {
              try {
                  var dados = new FormData();
                  dados.append('_token', this.token);
                  navigator.sendBeacon(this.beacon, dados);
              } catch (e) {}
          },
          enviar: function () {
              this.registrar();
              window.location.href = this.link;
          }
      }"
      @if ($abrirWhatsapp) x-init="setTimeout(function () { enviar(); }, 1500)" @endif>

    <div class="text-center">
        <a href="{{ route('home') }}"><img src="{{ asset('images/logo.png') }}" alt="{{ \App\Support\Setting::get('nome_salao') }}" class="mx-auto h-20 w-20 rounded-full"></a>
    </div>

    <div class="card mt-6 bg-white p-6 sm:p-8">
        <x-icone nome="check-circulo" class="w-7 h-7 text-luna-gold" />
        @if ($clienteRecorrente)
            <p class="sobretitulo mt-4">Que bom te ver de novo!</p>
        @endif
        <h1 class="mt-2 font-serif text-3xl sm:text-4xl">Recebemos seu pedido!</h1>
        <p class="mt-2 text-sm text-gray-700">Envie a mensagem no WhatsApp para o salão confirmar.</p>

        <p class="mt-6 flex items-baseline justify-between gap-4 border-y border-luna-sand py-3">
            <span class="text-sm text-gray-600">Código do agendamento</span>
            <span class="font-mono text-xl tracking-widest">{{ $ag->codigo }}</span>
        </p>

        <dl class="mt-4 space-y-3 text-sm">
            <div class="flex justify-between gap-4 border-b border-luna-sand pb-2"><dt class="text-gray-600">Serviço</dt><dd class="text-right font-medium">{{ $ag->servico->nome }}</dd></div>
            <div class="flex justify-between gap-4 border-b border-luna-sand pb-2"><dt class="text-gray-600">Profissional</dt><dd class="text-right font-medium">{{ $ag->profissional->nome_exibicao }}</dd></div>
            <div class="flex justify-between gap-4 border-b border-luna-sand pb-2"><dt class="text-gray-600">Data</dt><dd class="text-right font-medium">{{ \App\Support\WhatsAppLink::dataExtenso($ag->inicio) }}</dd></div>
            <div class="flex justify-between gap-4 border-b border-luna-sand pb-2"><dt class="text-gray-600">Horário</dt><dd class="text-right font-medium">{{ $ag->inicio->format('H:i') }}</dd></div>
            <div class="flex justify-between gap-4"><dt class="text-gray-600">Situação</dt><dd><x-badge-status :status="$ag->status" /></dd></div>
        </dl>

        @if ($ag->servico->requer_avaliacao)
            <p class="mt-4 rounded-md border-l-2 border-luna-gold bg-luna-cream px-3 py-2 text-xs text-luna-brown">Este serviço requer avaliação: o valor e o tempo serão confirmados pelo WhatsApp.</p>
        @endif

        <div class="mt-8 space-y-3">
            <a href="{{ $linkWhatsapp }}" @click.prevent="enviar()" class="btn-whatsapp w-full py-3.5 text-base">
                <x-icone nome="whatsapp" class="w-5 h-5" /> Enviar no WhatsApp
            </a>
            @if ($abrirWhatsapp)
                <p class="text-center text-xs text-gray-600" role="status">Abrindo o WhatsApp... Se não abrir, toque no botão acima.</p>
            @endif
            <a href="{{ $linkGoogle }}" target="_blank" rel="noopener" class="btn-secundario w-full">
                <x-icone nome="calendario" class="w-4 h-4 text-luna-gold" /> Adicionar ao Google Agenda
            </a>
            <a href="{{ route('agendamento.ics', $ag->token_publico) }}" class="block text-center text-xs text-gray-600 underline">Baixar arquivo de calendário (.ics)</a>
        </div>
    </div>

    <p class="mt-6 text-center text-sm"><a href="{{ route('home') }}" class="link">Voltar ao site</a></p>
</main>
@endsection
