# SIGA — Sistema Integrado de Gestão de Associados

Aplicação PHP (arquitetura MVC, sem frameworks externos) para a gestão de
associados da **União dos Escoteiros Portugueses (UEP)**, com base de dados
**MariaDB**.

## 1. Requisitos

- PHP 8.1 ou superior, com as extensões `pdo_mysql` e `mbstring`
- MariaDB 10.6+ (o script usa `utf8mb4_uca1400_as_ci`, disponível a partir do MariaDB 10.10/11; se a sua versão for anterior, substitua por `utf8mb4_unicode_ci` no script SQL)
- Servidor Apache com `mod_rewrite` (os `.htaccess` incluídos tratam do encaminhamento) — ou configuração equivalente em Nginx (ver secção 5)

Não é necessário Composer: existe um autoloader simples em `app/autoload.php`.
Se preferir Composer, o `composer.json` incluído define o mesmo mapeamento
PSR-4 (`App\` → `app/`) — corra `composer install` e troque o `require` em
`public/index.php` para `vendor/autoload.php`.

## 2. Estrutura do projeto

```
siga/
├── config/
│   └── config.php                # configuração (BD, sessão, nome da app, documentos)
├── app/
│   ├── Core/                    # Router, Controller e Model base, BD, Sessão, Data, Documentos
│   ├── Controllers/              # Auth, Dashboard, Associados, Moradas, Companhias
│   ├── Models/                   # Um modelo por entidade da base de dados
│   ├── Views/                    # Vistas PHP, organizadas por controlador
│   └── autoload.php
├── database/
│   └── SIGA_Criacao_BD.sql      # o script que forneceu, para criação da BD
├── public/                       # DocumentRoot do servidor web
│   ├── index.php                 # front controller (único ponto de entrada)
│   ├── .htaccess
│   └── assets/css, assets/js
├── .htaccess                     # redirecciona a raiz para public/
└── composer.json
```

## 3. Instalação

O projecto inclui **dois** scripts SQL, para dois cenários diferentes:

- **`database/SIGA_Criacao_BD.sql`** — criação completa de raiz. Use este
  se está a instalar o SIGA pela primeira vez (base de dados nova).
- **`database/SIGA_Migracao_v01.12_para_v01.13.sql`** — migra uma base de
  dados já na v01.12 do SIGA para a v01.13, preservando os dados
  existentes. Use este **em vez do** script de criação se já tem uma
  instalação do SIGA na v01.12. **Faça sempre uma cópia de segurança
  antes de correr este script** — as instruções estão no cabeçalho do
  próprio ficheiro.

  > **Política de migrações**: cada nova versão com alterações de schema
  > traz apenas a migração do seu passo imediatamente anterior — nunca a
  > história completa acumulada. Se a sua base de dados estiver numa
  > versão mais antiga (ex.: ainda no schema original, anterior a
  > qualquer versão do SIGA), precisa dos scripts de migração das versões
  > intermédias, que fizeram parte dos respectivos pacotes anteriores.

### 3.1 Instalação de raiz (base de dados nova)

1. **Criar a base de dados** — importe o script de criação:
   ```bash
   mysql -u root -p < database/SIGA_Criacao_BD.sql
   ```
   Isto cria a base de dados `siga`, todas as tabelas, os dados de referência
   (nacionalidades, secções, tipos de relação, etc.) e um utilizador inicial
   `Administrador`.

2. **Criar um utilizador de aplicação** dedicado (evite usar `root`):
   ```sql
   CREATE USER 'siga_app'@'localhost' IDENTIFIED BY 'escolha_uma_password_forte';
   GRANT SELECT, INSERT, UPDATE, DELETE ON siga.* TO 'siga_app'@'localhost';
   FLUSH PRIVILEGES;
   ```

3. **Configurar a ligação à base de dados** — defina variáveis de ambiente no
   servidor web (recomendado):
   ```
   SIGA_DB_HOST=127.0.0.1
   SIGA_DB_NAME=siga
   SIGA_DB_USER=siga_app
   SIGA_DB_PASS=escolha_uma_password_forte
   ```
   Ou edite directamente `config/config.php` (menos recomendado em produção).

4. **Utilizador inicial** — o script já cria o utilizador `Administrador`
   com a palavra-passe **`admin@ueo2026`**. Altere-a assim que possível
   (após o primeiro login, ou já agora por SQL):
   ```bash
   php -r "echo password_hash('a_sua_nova_password', PASSWORD_BCRYPT), PHP_EOL;"
   ```
   ```sql
   UPDATE siga.utilizadores
   SET Password = '<hash_gerada_acima>', Email = 'admin@uep.pt'
   WHERE Nome = 'Administrador';
   ```

5. **Apontar o servidor web para `public/`** (ver secção 5) e aceder a
   `/login` com o utilizador `Administrador` e a palavra-passe definida.

### 3.2 Migração de uma instalação existente (v01.12 → v01.13)

Se já tem uma base de dados SIGA na v01.12, com dados reais que quer
preservar:

1. **Faça uma cópia de segurança** antes de mais nada:
   ```bash
   mysqldump -u <utilizador> -p siga > backup_antes_da_v01.13.sql
   ```
2. **Corra o script de migração**:
   ```bash
   mysql -u <utilizador> -p siga < database/SIGA_Migracao_v01.12_para_v01.13.sql
   ```
3. Se a sua base de dados **não** estiver ainda na v01.12 (por exemplo,
   ainda está no schema original, ou numa versão mais antiga), precisa
   primeiro de aplicar os scripts de migração das versões intermédias
   correspondentes, antes deste.
4. Configure `config/config.php` (ou variáveis de ambiente) e o utilizador
   de aplicação como nos passos 2-3 da secção 3.1, se ainda não estiverem
   definidos.

## 4. Módulos incluídos até à v01.02

- **Autenticação** (`/login`), com sessões seguras (cookie `HttpOnly`,
  `SameSite=Lax`) e protecção CSRF em todos os formulários.
- **Painel principal** (`/`) com contagem de associados activos/inactivos e
  distribuição por secção.
- **Gestão de Associados** (`/associados`) — módulo central:
  - Listagem com pesquisa por nome/número e filtros por secção e estado;
  - Formulário de registo completo, com **datas em dd/mm/aaaa** (máscara
    automática e validação de datas não-futuras), que cria numa única
    transacção: pessoa, morada, contactos, associado, secção, companhia,
    encarregados de educação, contactos de emergência, ficha de saúde e
    consentimentos RGPD, além do evento de "Admissão" (com a data de
    inscrição escolhida, nunca a data do sistema);
  - Ficha de detalhe com todos os dados relacionados;
  - Edição dos dados base, com histórico automático ao mudar de secção ou
    companhia;
  - **Desactivação/reactivação com registo automático de evento**
    ("Desactivação"/"Reactivação"), com data e observações indicadas pelo
    utilizador — a reactivação nunca associa automaticamente a nenhuma
    companhia (regra 10.2).
- **Gestão de morada** (associados e companhias) — duas operações distintas:
  - **Corrigir**: altera os dados da morada existente (ex.: corrigir um
    número de porta), afectando todos os que a partilham;
  - **Substituir**: cria uma morada nova e fecha a ligação anterior,
    preservando o histórico (`DataInicio`/`DataFim`).
- **Gestão de contactos do associado** (`/associados/{id}/contactos`) —
  página única que reúne a morada (com as mesmas operações de corrigir/
  substituir acima) e os contactos generalizados (telemóvel, telefone,
  email, ...): listar, adicionar, editar e remover, com verificação de que
  o contacto pertence de facto ao associado antes de qualquer alteração.
- **Companhias** (`/companhias`) — listagem e detalhe com a morada actual,
  incluindo a Chefia Nacional. A criação/edição dos dados base das
  companhias fica para a página de administração (próxima versão).

- **Progressão entre secções** (regra 28) — ao mudar a secção de um
  associado já existente, em uso normal só é possível avançar (podendo
  saltar secções, ex.: um lobito que se afasta pode voltar directamente
  como Sénior) — nunca recuar. Não se aplica ao registo inicial (a
  primeira secção pode ser qualquer uma). Uma opção explícita "Isto é uma
  correcção" permite recuar quando necessário (corrigir um erro), sempre
  registada no histórico de eventos.
- **Email associativo dos dirigentes** (regra 27) — associados na secção
  "Chefia" têm de ter um contacto "Email Associativo", para além do email
  pessoal: obrigatório ao registar directamente na Chefia, exigido (já
  existente) ao mudar de secção para a Chefia via edição, e protegido
  contra remoção acidental enquanto o associado lá permanecer.

- **Companhia local, Chefia Nacional e órgãos** (regra 29) — um dirigente
  pode estar simultaneamente numa companhia local e na Chefia Nacional, ou
  numa companhia local e num ou mais órgãos, ou só num ou mais órgãos.
  Nova tabela `orgaos`, já preenchida (Mesa do Indaba, Conselho Fiscal,
  Conselho Jurisdicional, Academia de Formação — regra 30), e ligação
  `associados_orgaos` com pertença múltipla e simultânea. Disponível tanto
  no registo como na edição do associado.
- **Chefia Nacional restrita a dirigentes** (regra 31) — só um associado
  na secção "Chefia" pode pertencer à Chefia Nacional; validado no registo
  e na edição. Não se aplica (para já) aos órgãos.
- **Formador e insígnia de madeira** (regra 32) — um dirigente pode ser
  formador e/ou ter insígnia de madeira; quando tem insígnia, a data de
  atribuição é obrigatória (dd/mm/aaaa, nunca futura). Restrito a
  dirigentes (secção "Chefia"), validado no registo e na edição.

- **Cargos de dirigentes** (regra 34) — um dirigente pode ter um ou mais
  cargos em simultâneo (Chefe da Companhia, Subchefe da Companhia,
  Colaborador da Chefia, Chefe de Divisão, Subchefe de Divisão, Chefe de
  Secretaria, Chefe de Finanças, Assistente Confessional, Chefe Regional,
  Chefe Regional Adjunto, Chefe Nacional, Chefe Nacional Adjunto),
  actualizáveis ao longo do tempo (histórico) e restritos a dirigentes
  (secção "Chefia"). **Nota**: por agora nenhuma combinação de cargos é
  impedida — ainda não há uma lista de incompatibilidades definida (ver
  regra 34.2 no documento de regras de negócio).

- **Gestão de utilizadores** (`/utilizadores`) — criação, edição e desactivação de acessos à aplicação, com atribuição do grupo de administrador. Acesso restrito a utilizadores administradores (o link no menu só aparece para eles, e a página bloqueia o acesso a qualquer outro). Salvaguardas incluídas: não é possível remover os próprios privilégios de administrador, desactivar a própria conta, nem remover o último administrador activo do sistema.

### Nota sobre o número de documento (Cartão de Cidadão)

O número é sempre tratado como texto (nunca convertido para inteiro), mas o
preenchimento automático com zeros à esquerda está **inactivo** até a
largura exacta ser confirmada — ver `config/config.php` → `documentos.largura_cc`.
Assim que souber o valor correcto, basta preenchê-lo aí; não é necessária
nenhuma alteração de código.

## 5. Próximos passos sugeridos

Este é um esqueleto funcional e extensível. Áreas naturais para continuar:

- **Página de administração** (backoffice) para gerir tabelas de referência
  (nacionalidades, confissões religiosas, tipos de contacto/relação/evento,
  secções) e os dados base das companhias (criação/edição/inactivação);
- Gestão de utilizadores (`utilizadores`, `utilizadores_companhias`,
  `utilizadores_associados`) e permissões por companhia;
- Histórico de alterações à ficha de saúde (a tabela `fichas_saude_historico`
  já existe no modelo, mas ainda não está a ser escrita pela aplicação);
- Confirmar a largura do número do Cartão de Cidadão e activar o
  preenchimento automático com zeros (`config/config.php` → `documentos.largura_cc`);
- Exportações (ex.: listas de associados por secção, em CSV/PDF);
- Testes automatizados.

## 6. Regras de negócio

O ficheiro [`docs/regras_de_negocio.txt`](docs/regras_de_negocio.txt) é a
especificação funcional fixa do SIGA. Qualquer versão nova deve ser
confrontada com este documento antes de ser gerada — nenhuma decisão já
fechada aí pode ser alterada ou esquecida sem uma decisão explícita.

### Exemplo de VirtualHost Apache

```apache
<VirtualHost *:80>
    ServerName siga.local
    DocumentRoot /var/www/siga/public
    <Directory /var/www/siga/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Equivalente em Nginx

```nginx
server {
    listen 80;
    server_name siga.local;
    root /var/www/siga/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php$is_args$args;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 7. Histórico de versões

Esta secção é actualizada a cada nova versão entregue, com as alterações
feitas desde a versão anterior. Mais recente primeiro.

### v01.25
- Nova página de gestão de utilizadores (`/utilizadores`), com acesso restrito a administradores: criar, editar (nome, email, palavra-passe, grupo de administrador, estado).
- Salvaguardas: impede remover os próprios privilégios de administrador, desactivar a própria conta, ou remover o último administrador activo do sistema.

### v01.24
- Logótipos do cabeçalho e do rodapé aumentados de novo (cabeçalho: 52px; rodapé: 92px).
- Este histórico de versões passa a fazer parte permanente do README.

### v01.23
- Logótipo do rodapé aumentado (44px → 64px).
- Todos os ficheiros de texto do projecto convertidos para terminação de linha CRLF (`\r\n`).

### v01.22
- Logótipo do rodapé aumentado (28px → 44px).

### v01.21
- Logótipo do SIGA acrescentado ao canto esquerdo do rodapé.

### v01.20
- "Powered By MLX-PT" e o número de versão acrescentados ao canto direito do rodapé.
- Número de versão passa a estar centralizado em `config/config.php` (`app.versao`).

### v01.19
- Corrigido o login: passa a aceitar apenas nome de utilizador (deixou de aceitar email).

### v01.18
- Logótipo da UEP acrescentado ao cabeçalho (canto esquerdo), sobre fundo branco para contraste.
- Logótipo do SIGA no login aumentado (92px → 220px).

### v01.17
- Tipo de letra alterado para Arial Rounded (com Varela Round como alternativa livre).

### v01.16
- Esquema de cores de toda a aplicação alinhado com o logótipo (azul-marinho/dourado), antes só aplicado ao login.

### v01.15
- Logótipo do SIGA no login reduzido de tamanho.
- Cores do ecrã de login alteradas para azul-marinho/dourado, a condizer com o logótipo.

### v01.14
- Logótipo do SIGA acrescentado ao ecrã de login.

### v01.13
- Nova funcionalidade: cargos de dirigentes (regra 34) — tabelas `cargos`/`associados_cargos`, com pertença múltipla e simultânea, restrita a dirigentes.
- Lista de 12 cargos semeada (Chefe da Companhia, Subchefe da Companhia, Colaborador da Chefia, Chefe de Divisão, Subchefe de Divisão, Chefe de Secretaria, Chefe de Finanças, Assistente Confessional, Chefe Regional, Chefe Regional Adjunto, Chefe Nacional, Chefe Nacional Adjunto).
- Script de migração `SIGA_Migracao_v01.12_para_v01.13.sql` incluído.

### v01.12
- Corrigida a política de scripts de migração: cada pacote passa a trazer apenas a migração do passo imediatamente anterior, nunca a história acumulada.
- Ficheiro de migração renomeado para reflectir com precisão o que cobre.

### v01.11
- Introduzido o segundo ficheiro SQL do pacote: script de migração, além do de criação de raiz (alteração explícita à regra 2.3 original).

### v01.10
- Chefia Nacional, Formador e Insígnia de Madeira restritos a dirigentes (associados na secção "Chefia"), validado no registo e na edição.

### v01.09
- Tabela `orgaos` semeada (Mesa do Indaba, Conselho Fiscal, Conselho Jurisdicional, Academia de Formação).
- Novos atributos do associado: Formador (sim/não) e Insígnia de Madeira (sim/não, com data de atribuição obrigatória quando aplicável).

### v01.08
- Novo modelo de enquadramento de dirigentes: companhia local, Chefia Nacional e órgãos passam a poder coexistir (a Chefia Nacional deixou de ser exclusiva com a companhia local).
- Novas tabelas `orgaos` (vazia nesta versão) e `associados_orgaos`, com pertença múltipla e simultânea.

### v01.07
- Acrescentada via de correcção explícita para mudanças de secção que representem um recuo ("Isto é uma correcção"), sempre registada como evento.

### v01.06
- Corrigida a regra de progressão entre secções: passa a permitir avançar para qualquer secção posterior na sequência (não só a imediatamente a seguir) — nunca recuar em uso normal.

### v01.05
- Email associativo passa a ser obrigatório para associados na secção "Chefia", validado no registo e na mudança de secção; protegido contra remoção enquanto o associado estiver na Chefia.

### v01.04
- Novo tipo de contacto "Email Associativo", para uso dos dirigentes.

### v01.03
- Novo ecrã único de gestão de contactos do associado (`/associados/{id}/contactos`), reunindo morada e contactos.

### v01.02
- Datas em todo o sistema passam a usar o formato dd/mm/aaaa (com máscara e validação de datas não-futuras).
- Desactivação/reactivação de associados passam a registar sempre um evento correspondente.
- Contactos de emergência deixam de depender de um registo em "pessoas".
- Introduzida a correcção vs. substituição de morada, e a gestão de morada de companhias/Chefia Nacional.
- `config.php` movido para `config/config.php`.
- Corrigido erro de login (parâmetro SQL nomeado duplicado).

### v01.00 – v01.01
- Primeira entrega: esqueleto MVC completo, autenticação, painel principal e módulo de gestão de associados (registo, listagem, edição, ficha de detalhe, desactivação/reactivação).
- Corrigido o `config.php` para usar as credenciais fornecidas.
