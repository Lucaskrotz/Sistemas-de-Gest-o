<?php

// Registro dos módulos: alimenta home, sidebar, cor de destaque, rotas e /demo/{modulo}.
// Menu: ['label', 'rota' => nome da rota, 'icone' => nome Lucide, 'ativo' => padrão(ões) routeIs (opcional)];
// itens sem rota registrada são ocultados.
return [
    'erp' => [
        'nome' => 'Gestor ERP',
        'icone' => 'building-2',
        'rota' => 'erp',
        'cor' => '#2563eb',
        'descricao' => 'Clientes, produtos e pedidos com CRUD completo e DataTables.',
        'menu' => [
            ['label' => 'Dashboard', 'rota' => 'erp.index', 'icone' => 'layout-dashboard'],
            ['label' => 'Pedidos', 'rota' => 'erp.pedidos.index', 'icone' => 'shopping-cart', 'ativo' => 'erp.pedidos.*'],
            ['label' => 'Clientes', 'rota' => 'erp.clientes.index', 'icone' => 'users', 'ativo' => 'erp.clientes.*'],
            ['label' => 'Produtos', 'rota' => 'erp.produtos.index', 'icone' => 'package', 'ativo' => 'erp.produtos.*'],
        ],
    ],
    'chamados' => [
        'nome' => 'Help Desk',
        'icone' => 'life-buoy',
        'rota' => 'chamados',
        'cor' => '#7c3aed',
        'descricao' => 'Kanban com drag-and-drop, SLA e histórico de atendimento.',
        'menu' => [
            ['label' => 'Quadro', 'rota' => 'chamados.index', 'icone' => 'kanban'],
            ['label' => 'Novo chamado', 'rota' => 'chamados.create', 'icone' => 'circle-plus'],
        ],
    ],
    'indicadores' => [
        'nome' => 'Indicadores',
        'icone' => 'chart-line',
        'rota' => 'indicadores',
        'cor' => '#0891b2',
        'descricao' => 'Dashboard com Chart.js, AJAX e filtros de período.',
        'menu' => [['label' => 'Painel', 'rota' => 'indicadores.index', 'icone' => 'chart-line']],
    ],
    'financeiro' => [
        'nome' => 'Financeiro',
        'icone' => 'landmark',
        'rota' => 'financeiro',
        'cor' => '#059669',
        'descricao' => 'Títulos a receber com baixa automática via webhook simulado.',
        'menu' => [
            ['label' => 'Títulos a receber', 'rota' => 'financeiro.index', 'icone' => 'receipt', 'ativo' => ['financeiro.index', 'financeiro.titulos.*']],
            ['label' => 'Webhooks', 'rota' => 'financeiro.webhooks', 'icone' => 'webhook'],
        ],
    ],
    'pagamentos' => [
        'nome' => 'Pagamentos',
        'icone' => 'credit-card',
        'rota' => 'pagamentos',
        'cor' => '#d97706',
        'descricao' => 'Gateway fake com eventos PAYMENT_* e linha do tempo.',
        'menu' => [
            ['label' => 'Transações', 'rota' => 'pagamentos.index', 'icone' => 'arrow-left-right', 'ativo' => ['pagamentos.index', 'pagamentos.show']],
            ['label' => 'Nova cobrança', 'rota' => 'pagamentos.create', 'icone' => 'circle-plus'],
            ['label' => 'Eventos', 'rota' => 'pagamentos.eventos', 'icone' => 'activity'],
        ],
    ],
    'tenancy' => [
        'nome' => 'Multi-tenant',
        'icone' => 'layers',
        'rota' => 'multitenant',
        'cor' => '#db2777',
        'descricao' => 'Dados isolados por empresa com tenant_id e global scope.',
        'menu' => [
            ['label' => 'Painel', 'rota' => 'tenancy.index', 'icone' => 'layout-dashboard'],
            ['label' => 'Contatos', 'rota' => 'tenancy.contatos.index', 'icone' => 'contact', 'ativo' => 'tenancy.contatos.*'],
            ['label' => 'Oportunidades', 'rota' => 'tenancy.oportunidades.index', 'icone' => 'handshake'],
            ['label' => 'Equipe', 'rota' => 'tenancy.equipe', 'icone' => 'users'],
        ],
    ],
];
