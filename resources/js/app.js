import './bootstrap';

import Alpine from 'alpinejs';
import intersect from '@alpinejs/intersect';
import mask from '@alpinejs/mask';
import formAgendamento from './agendamento';
import { seletorHorario, concluirAtendimento, buscaCliente } from './painel';

Alpine.plugin(intersect);
Alpine.plugin(mask);
Alpine.data('formAgendamento', formAgendamento);
Alpine.data('seletorHorario', seletorHorario);
Alpine.data('concluirAtendimento', concluirAtendimento);
Alpine.data('buscaCliente', buscaCliente);

window.Alpine = Alpine;

Alpine.start();
