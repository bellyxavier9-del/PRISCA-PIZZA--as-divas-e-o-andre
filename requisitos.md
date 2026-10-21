# Prisca Pizza

## Objetivos
O sistema vai escolher uma cor aleatória e gerar uma combinação de cores baseado na escolha de uma 
outra cor que o usuário vai escolher, usando o circulo cromático.

### Stack Tecnlógico
- Backend: PHP estruturado com sessões nativas
- Banco de dados: MySQL (PDO para segurança)
- Frontend: HTML5, PHP, CSS, Tailwind CSS

## Perfis de usuário

### Perfil 1 — Cliente
* Pode criar uma conta, fazer login e editar seus próprios dados.
* Pode visualizar o cardápio, realizar pedidos e acompanhar o andamento das próprias compras.
* Pode consultar o histórico dos próprios pedidos.
* Não pode visualizar, alterar ou cancelar pedidos de outros clientes.
* Não pode acessar os painéis internos da pizzaria.

### Perfil 2 — Atendente de caixa
* Deve acessar um painel interno de gerenciamento de pedidos.
* Pode visualizar os novos pedidos, conferir as informações necessárias e encaminhá-los para a cozinha.
* Pode verificar o status dos pagamentos por meio da integração com o sistema de pagamento.
* Pode atualizar os estados do pedido conforme as permissões definidas.
* Pode organizar pedidos para retirada ou entrega.
* Não pode consultar senhas dos clientes nem acessar funções administrativas não autorizadas.

### Perfil 3 — Pizzaiolo
* Deve acessar um painel específico para a cozinha.
* Pode visualizar os pedidos encaminhados para preparo.
* Deve visualizar os produtos, tamanhos, sabores, adicionais e observações relevantes.
* Pode atualizar o pedido para “Em preparo” e “Pronto”.
* Não deve acessar dados de pagamento desnecessários ou informações pessoais que não sejam relevantes para a preparação.

### Regras gerais de segurança
* Cada usuário deve ter seu próprio acesso e perfil.
* As permissões devem ser verificadas no servidor, não apenas na interface do site.
* Usuários não autorizados não podem acessar páginas ou informações internas alterando endereços ou parâmetros.
* As senhas devem ser protegidas por hash seguro.
* As alterações de status devem ser registradas e realizadas somente por usuários autorizados.
* Os painéis internos devem atualizar os pedidos automaticamente, sem exigir que os funcionários recarreguem a página constantemente.
* O sistema deve manter os pedidos sincronizados para evitar duplicações ou perda de informações.
* Os clientes devem visualizar somente os dados dos próprios pedidos.
* O sistema deve respeitar a privacidade e proteger os dados pessoais dos usuários.

## Regras de negócio (CORE)
- Tratar senhas de usuários com hash bcript
- O sistema deve ter uma página de históricos e manter sempre os logs de qualquer alteração feita por 
qualquer usuário, para auditorias futuras.

##### Segurança e proteção de senhas
* O sistema deve proteger as senhas de todos os usuários cadastrados.
* As senhas nunca devem ser armazenadas em texto puro no banco de dados.
* O sistema deve utilizar uma função segura de hash de senhas, como Argon2id, bcrypt ou scrypt.
* Cada senha deve ser processada com um salt único, utilizando os recursos seguros da biblioteca escolhida.
* No login, o sistema deve verificar a senha informada comparando-a de forma segura com o hash armazenado.
* A IA e os administradores não devem ter acesso às senhas originais dos usuários.
* As senhas não devem aparecer em logs, mensagens de erro ou relatórios.
* O sistema deve utilizar HTTPS para proteger os dados durante a transmissão.
* O processo de recuperação de senha deve utilizar um mecanismo seguro de redefinição, sem revelar a senha antiga.
* As tentativas de login devem ser limitadas para reduzir o risco de ataques automatizados.
* As credenciais e os segredos do sistema devem ser armazenados de forma segura, nunca diretamente no código público do site.

## Regras da IA — Site de Pizzaria

### 1. Atendimento ao cliente
* A IA deve atender os clientes de maneira educada, clara e cordial.
* Deve responder dúvidas sobre o cardápio, ingredientes, preços, entrega e formas de pagamento.
* Deve utilizar apenas informações cadastradas no sistema.
* Caso não saiba uma resposta, deve informar que precisa de confirmação, sem inventar informações.

### 2. Pedidos
* A IA deve permitir que o cliente escolha os produtos, tamanhos, sabores e adicionais disponíveis.
* Deve confirmar os detalhes do pedido antes de finalizá-lo.
* Deve calcular corretamente o valor dos produtos, adicionais, descontos e entrega.
* Deve informar o valor total antes da confirmação da compra.
* Não deve finalizar pedidos sem as informações obrigatórias.

### 3. Preços e promoções
* A IA nunca deve inventar preços, cupons ou promoções.
* Deve respeitar os preços cadastrados no sistema.
* Deve aplicar descontos somente quando as condições da promoção forem atendidas.
* Deve informar as regras e a validade das promoções disponíveis.

### 4. Entregas
* A IA deve verificar se o endereço está dentro da área de entrega.
* Deve informar a taxa e o prazo estimado conforme os dados cadastrados.
* Não deve prometer horários de entrega que não possam ser confirmados.
* Deve oferecer a opção de retirada somente se ela estiver disponível.

### 5. Disponibilidade
* A IA deve verificar o horário de funcionamento da pizzaria.
* Não deve aceitar pedidos fora do horário permitido, salvo se houver agendamento disponível.
* Não deve vender produtos indisponíveis.
* Deve informar quando um produto estiver temporariamente indisponível.

### 6. Segurança e privacidade
* A IA deve solicitar apenas os dados necessários para realizar o pedido.
* Deve proteger os dados pessoais dos clientes.
* Não deve solicitar senhas bancárias ou compartilhar informações pessoais com outros clientes.
* Deve utilizar meios de pagamento seguros.

### 7. Confirmação e acompanhamento
* Antes de concluir o pedido, a IA deve apresentar um resumo com produtos, quantidades, adicionais, endereço, taxa de entrega e valor total.
* Deve solicitar a confirmação do cliente antes de enviar o pedido.
* Deve informar o status do pedido somente com base nos dados reais do sistema.
* Deve encaminhar o cliente para atendimento humano quando necessário.

## Regras Globais
- Use sempre PDO para conexão e queries no MySQL para evitar SQL Injections
- Mantenha o código limpo e comente apenas logicas complexas.
- Separe os arquivos de forma lógica: um arquivo para conexão com a base (bd.php) e scripts de backend isolados e views em HTML5/PHP, nunca faça o sistema como um monolito, deixe sempre separados todas as regras para facilitar os futuros upgrades.
 - Estilize as telas em Tailwind de forma responsiva priorizando o MobileFrist.
 - Retorne sempre as mensagens de erros de forma claras na interface para o usuário (TOAST)
 - sempre trate as mensagens de caixa de mensagens nativas do navegador em um MODAL
