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

1. **Criar a base de dados** — importe o script fornecido:
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
