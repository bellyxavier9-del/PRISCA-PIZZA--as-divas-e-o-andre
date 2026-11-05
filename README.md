# 🍕 Prisca Pizza — Sistema Completo de Gestão e Pedidos Online

Sistema completo e profissional para pedidos de pizzas, esfihas, bebidas e sobremesas artesanais pela internet, com acompanhamento em tempo real pelo cliente e painéis dedicados para o Atendente de Caixa, Pizzaiolo e Entregador.

---

## 🚀 Stack Tecnológica

- **Backend:** PHP 8+ estruturado com sessões nativas (`$_SESSION`, cookies HttpOnly)
- **Banco de Dados:** MySQL com **PDO** (Prepared Statements contra SQL Injection) e inicialização resiliente
- **Frontend:** HTML5 semântico, PHP, CSS3, **Tailwind CSS** (design responsivo priorizando **Mobile First**)
- **Componentes Globais:** Sistema de notificações **TOAST** e caixas de diálogo e confirmação em **MODAL** customizado (substitui caixas nativas do navegador)
- **Atualização em Tempo Real:** Mecanismo de polling assíncrono via Fetch API a cada poucos segundos sem recarregar a página
- **Inteligência Artificial:** Assistente Virtual "Prisca AI" integrado cumprindo todas as 7 regras de atendimento

---

## 👥 Perfis de Usuário & Permissões (Validação Server-Side)

| Perfil | E-mail de Demonstração | Senha | Acesso e Responsabilidades |
|---|---|---|---|
| **1. Cliente** | `cliente@priscapizza.com` | `senha123` | Cria conta, edita dados próprios, monta pizzas (tamanhos, 1/2 a 1/2, bordas), faz pedidos, simula pagamentos e rastreia status em tempo real. |
| **2. Atendente de Caixa** | `caixa@priscapizza.com` | `senha123` | Acessa o Painel do Caixa, confere novos pedidos recebidos, valida pagamentos (Pix, Cartão, Dinheiro), despacha para a cozinha, conclui retiradas no balcão e cancela pedidos. |
| **3. Pizzaiolo** | `pizzaiolo@priscapizza.com` | `senha123` | Acessa o KDS (Kitchen Display System), visualiza pedidos encaminhados para preparo com detalhes culinários (tamanhos, sabores, bordas, adicionais e observações). **Não tem acesso a dados financeiros ou telefones**. Atualiza para "Em preparo" e "Pronto". |
| **4. Entregador** | `entregador@priscapizza.com` | `senha123` | Acessa o Painel de Entregas, visualiza pedidos prontos, assume rota ("Saiu para entrega"), visualiza endereço completo e telefone **apenas dos pedidos que está entregando**, confere se há valor a receber em dinheiro e atualiza para "Entregue". |
| **5. Administrador** | `admin@priscapizza.com` | `senha123` | Visão geral da pizzaria, acesso irrestrito a todos os setores e consulta da página de **Auditoria e Histórico de Logs**. |

---

## 🔄 Fluxo de Status do Pedido

Os pedidos seguem o ciclo de vida rigoroso definido nas regras de negócio:

```
[Cliente Realiza Pedido] 
           ↓
      1. Recebido (Atendente de Caixa confere pagamento e dados)
           ↓ (Encaminhar para a Cozinha)
      2. Em preparo (Pizzaiolo inicia a montagem e forno)
           ↓ (Marcar como Pronto)
      3. Pronto
         ├─ Se Retirada no Balcão ──→ Caixa conclui como "Entregue"
         └─ Se Entrega em Domicílio
                ↓ (Entregador assume pedido)
           4. Saiu para entrega (Em trânsito com motoboy)
                ↓ (Confirmar entrega no destino)
           5. Entregue (Concluído)
```
*(Pedidos também podem ser cancelados justificadamente pelo Atendente de Caixa com registro obrigatório de motivo).*

---

## 💳 Formas de Pagamento Simuladas

1. **PIX Instantâneo:** Gera código fictício padrão Copia e Cola (BR Code) e **QR Code dinâmico** na tela para simulação imediata.
2. **Cartão de Crédito / Débito:** Simulação segura de aprovação online sem armazenar dados sensíveis de cartões de clientes.
3. **Dinheiro:** Pagamento no ato da entrega ou balcão, com campo opcional para cálculo de troco ("Troco para quanto?").

---

## 🛡️ Regras de Segurança e Proteção de Dados (CORE)

- **Senhas:** Criptografadas com `password_hash($senha, PASSWORD_BCRYPT)` com salt único automático por usuário e conferidas com `password_verify()`.
- **Proteção contra Força Bruta (Rate Limiting):** Monitoramento de tentativas de login inválidas. Após 5 falhas consecutivas, a conta é bloqueada temporariamente por 15 minutos.
- **Tratamento de Entradas:** Sanitização contra XSS (`htmlspecialchars`) e queries preparadas via PDO contra SQL Injection.
- **Isolamento de Dados:** Validações de sessão e perfil realizadas no servidor em cada endpoint e página interna, impedindo acesso via alteração de parâmetros de URL.
- **Auditoria Completa (`logs_auditoria`):** Toda alteração de status, criação de pedido, login ou violação é persistida no banco registrando:
  - **Quem fez** (`usuario_id`, `usuario_nome`, `usuario_perfil`)
  - **Quando fez** (Data e hora com precisão de segundos)
  - **O que mudou** (`status_anterior`, `status_novo`, `detalhes`, `ip`)

---

## 🤖 Regras da IA — Assistente Virtual "Prisca AI"

O sistema possui um widget flutuante de Inteligência Artificial integrado em todas as telas de clientes (`includes/ai_widget.php` e `api/ai_chat.php`) que atende estritamente às 7 regras:

1. **Atendimento ao cliente:** Comunicação cordial, clara e educada.
2. **Cardápio & Pedidos:** Explica sabores reais de pizzas, esfihas, adicionais e tamanhos (Broto, Média, Grande e Família).
3. **Preços e Promoções:** Respeita estritamente os preços do banco de dados, sem inventar valores ou cupons falsos.
4. **Entregas:** Informa a taxa padrão (R$ 8,00 fixa) e prazos médios (40-50 min entrega, 20-30 min retirada).
5. **Disponibilidade:** Consulta o horário de funcionamento (Terça a Domingo das 18h às 23h30).
6. **Segurança e Privacidade:** Jamais solicita senhas bancárias ou expõe dados de outros clientes.
7. **Acompanhamento:** Permite ao cliente digitar o código do pedido (ex: `#PRISCA-1001`) para consultar o status real, itens e previsão!

---

## 📁 Estrutura de Arquivos Modular

```
prisca-appsite/
├── index.php                 # Página Inicial (Hero, Destaques, Como Funciona)
├── cardapio.php              # Cardápio com Filtros e Modal de Customização
├── carrinho.php              # Sacola de Compras, Modalidade e Checkout
├── meus_pedidos.php          # Histórico de Pedidos do Cliente
├── rastreio.php              # Acompanhamento do Pedido com Auto-Refresh
├── perfil.php                # Edição de Dados Cadastrais e Endereço
├── login.php                 # Login com Proteção Brute-Force e Atalhos Rápidos
├── cadastro.php              # Cadastro de Novos Clientes
├── logout.php                # Encerramento Seguro de Sessão
├── setup.php                 # Painel de Diagnóstico e Login com 1 Clique
│
├── config/
│   ├── config.php            # Constantes, Fuso Horário e Horários de Fornada
│   └── bd.php                # Conexão PDO MySQL (e fallback auto-inicializável)
│
├── includes/
│   ├── auth.php              # Controle de Sessão, Perfis e Bloqueio de Tentativas
│   ├── functions.php         # Auditoria, Formatação Moeda, Toasts e Badges
│   ├── header.php            # Cabeçalho HTML5, Tailwind CDN e Meta Tags
│   ├── footer.php            # Rodapé, Barra Inferior Mobile First e Scripts
│   ├── navbar.php            # Menu Superior Responsivo com Indicadores
│   ├── modal.php             # Modal Customizado (Substitui alert/confirm nativos)
│   └── ai_widget.php         # Widget Flutuante da Prisca AI
│
├── api/
│   ├── pedidos.php           # Endpoint de Polling e Atualização de Status
│   ├── checkout.php          # Endpoint de Processamento do Pedido
│   ├── ai_chat.php           # Endpoint da Prisca AI baseado nas 7 regras
│   └── auditoria.php         # Endpoint dos Logs de Auditoria
│
├── painel/
│   ├── index.php             # Gateway dos Colaboradores / Dashboard Admin
│   ├── caixa.php             # Painel do Caixa (Perfil 2)
│   ├── cozinha.php           # Painel da Cozinha KDS (Perfil 3)
│   ├── entregas.php          # Painel de Entregas (Perfil 4)
│   └── auditoria.php         # Histórico Completo de Auditoria
│
├── assets/
│   ├── css/custom.css        # Animações, Toasts e Estilos de Impressão Térmica
│   └── js/
│       ├── main.js           # Gerenciador de Toasts e Modais
│       ├── cart.js           # Sacola, Cálculos e Customização de Pizzas
│       ├── panels.js         # Auto-Polling (6s) e Alertas dos Painéis
│       └── ai_chat.js        # Chatbot da Prisca AI
│
└── database/
    ├── schema.sql            # Definição DDL das Tabelas e Índices MySQL
    └── seed.sql              # Dados Iniciais (Categorias, Pizzas, Usuários)
```

---

## ⚙️ Como Executar o Sistema

### Opção 1: Com XAMPP, WAMP ou Laragon (Recomendado)
1. Certifique-se de que o **Apache** e o **MySQL** estejam iniciados no seu painel (ex: XAMPP Control Panel).
2. Coloque a pasta do projeto dentro de `htdocs` (ex: `C:\xampp\htdocs\prisca appsite`).
3. Abra seu navegador em: `http://localhost/prisca appsite/`
4. Acesse `http://localhost/prisca appsite/setup.php` para validar o banco de dados e usar os botões de **1 clique** para navegar em qualquer um dos 4 perfis!

### Opção 2: Servidor Embutido do PHP (Rápido)
Se tiver o PHP instalado no seu terminal:
```bash
cd "c:\Users\Pichau\OneDrive\Desktop\prisca appsite"
php -S localhost:8000
```
Depois abra no navegador: `http://localhost:8000`

---

## 🎯 Roteiro Rápido de Testes Recomendado

1. Acesse `setup.php` e clique no botão **"Entrar como Cliente"**.
2. Vá até o **Cardápio**, escolha uma pizza (ex: Margherita), selecione o tamanho Grande, adicione Borda de Catupiry e adicione à sacola.
3. Abra a **Sacola**, selecione Entrega em Domicílio e forma de pagamento Pix. Clique em **Finalizar Pedido**.
4. Você será direcionado para `rastreio.php` com o stepper visual em tempo real.
5. Em outra aba (ou navegando pelo menu), acerte o perfil para **Caixa** (`painel/caixa.php`):
   - O pedido aparecerá com efeito sonoro e visual;
   - Clique em **"Despachar para Cozinha"**.
6. Entre no painel da **Cozinha** (`painel/cozinha.php`):
   - O ticket aparecerá na esteira do forno;
   - Clique em **"Iniciar Preparo"** e depois **"Marcar como Pronto"**.
7. Entre no painel de **Entregas** (`painel/entregas.php`):
   - Clique em **"Pegar este Pedido & Iniciar Rota"** (o status mudará para *Saiu para entrega*);
   - Veja o endereço, mapa e WhatsApp do cliente;
   - Clique em **"Confirmar Entrega Concluída"**.
8. Volte à aba do cliente no `rastreio.php`: veja o stepper atualizado automaticamente para **"Entregue"**!
9. Acesse `painel/auditoria.php`: observe todo o histórico detalhado registrado com usuário, data/hora e IP.
10. Abra o chat flutuante da **Prisca AI** e digite o código do pedido (ex: `#PRISCA-1001`) ou tire dúvidas sobre pizzas e horários.
