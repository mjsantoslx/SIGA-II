DROP DATABASE IF EXISTS siga;

CREATE DATABASE siga
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_uca1400_as_ci;

USE siga;

-- ============================================================================
-- TABELAS DE REFERÊNCIA
-- ============================================================================

CREATE TABLE nacionalidades (
    Id INT NOT NULL AUTO_INCREMENT,
    Nacionalidade VARCHAR(50) NOT NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_nacionalidades_nacionalidade (Nacionalidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE estados_civis (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(50) NOT NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_estados_civis_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE confissoes_religiosas (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(100) NOT NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_confissoes_religiosas_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE tipos_documento_identificacao (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(100) NOT NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_tdi_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE tipos_contacto (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(50) NOT NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_tipos_contacto_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE tipos_relacao (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(50) NOT NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_tipos_relacao_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE tipos_evento (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(100) NOT NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_tipos_evento_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- PESSOAS
-- Entidade comum a associados e pessoas externas.
-- ============================================================================

CREATE TABLE pessoas (
    Id INT NOT NULL AUTO_INCREMENT,
    Nome VARCHAR(150) NOT NULL,
    PRIMARY KEY (Id),
    KEY ix_pessoas_nome (Nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- MORADAS
-- Entidade independente e potencialmente partilhada.
-- ============================================================================

CREATE TABLE moradas (
    Id INT NOT NULL AUTO_INCREMENT,
    Morada VARCHAR(200) NOT NULL,
    CodPostal VARCHAR(20) NULL,
    IdConcelho INT NULL,
    IdDistrito INT NULL,
    Localidade VARCHAR(100) NULL,
    PRIMARY KEY (Id),
    KEY ix_moradas_codigo_postal (CodPostal),
    KEY ix_moradas_localidade (Localidade)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE pessoas_moradas (
    Id INT NOT NULL AUTO_INCREMENT,
    IdPessoa INT NOT NULL,
    IdMorada INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    KEY ix_pessoas_moradas_pessoa (IdPessoa),
    KEY ix_pessoas_moradas_morada (IdMorada),
    CONSTRAINT fk_pessoas_moradas_pessoa
        FOREIGN KEY (IdPessoa) REFERENCES pessoas(Id),
    CONSTRAINT fk_pessoas_moradas_morada
        FOREIGN KEY (IdMorada) REFERENCES moradas(Id),
    CONSTRAINT chk_pessoas_moradas_datas
        CHECK (DataFim IS NULL OR DataFim >= DataInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- CONTACTOS GENERALIZADOS DAS PESSOAS
-- ============================================================================

CREATE TABLE contactos (
    Id INT NOT NULL AUTO_INCREMENT,
    IdPessoa INT NOT NULL,
    IdTipoContacto INT NOT NULL,
    Valor VARCHAR(150) NOT NULL,
    PRIMARY KEY (Id),
    KEY ix_contactos_pessoa (IdPessoa),
    KEY ix_contactos_tipo (IdTipoContacto),
    CONSTRAINT fk_contactos_pessoa
        FOREIGN KEY (IdPessoa) REFERENCES pessoas(Id),
    CONSTRAINT fk_contactos_tipo
        FOREIGN KEY (IdTipoContacto) REFERENCES tipos_contacto(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- COMPANHIAS
-- ============================================================================

CREATE TABLE companhias (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(150) NOT NULL,
    ambito_global TINYINT(1) NOT NULL DEFAULT 0,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_companhias_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE companhias_moradas (
    Id INT NOT NULL AUTO_INCREMENT,
    IdCompanhia INT NOT NULL,
    IdMorada INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    KEY ix_companhias_moradas_companhia (IdCompanhia),
    KEY ix_companhias_moradas_morada (IdMorada),
    CONSTRAINT fk_companhias_moradas_companhia
        FOREIGN KEY (IdCompanhia) REFERENCES companhias(Id),
    CONSTRAINT fk_companhias_moradas_morada
        FOREIGN KEY (IdMorada) REFERENCES moradas(Id),
    CONSTRAINT chk_companhias_moradas_datas
        CHECK (DataFim IS NULL OR DataFim >= DataInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE companhias_contactos (
    Id INT NOT NULL AUTO_INCREMENT,
    IdCompanhia INT NOT NULL,
    IdTipoContacto INT NOT NULL,
    Valor VARCHAR(150) NOT NULL,
    PRIMARY KEY (Id),
    KEY ix_companhias_contactos_companhia (IdCompanhia),
    KEY ix_companhias_contactos_tipo (IdTipoContacto),
    CONSTRAINT fk_companhias_contactos_companhia
        FOREIGN KEY (IdCompanhia) REFERENCES companhias(Id),
    CONSTRAINT fk_companhias_contactos_tipo
        FOREIGN KEY (IdTipoContacto) REFERENCES tipos_contacto(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- SECÇÕES
-- Sem flag Activo, conforme definido.
-- ============================================================================

CREATE TABLE secoes (
    Id INT NOT NULL AUTO_INCREMENT,
    Designacao VARCHAR(100) NOT NULL,
    NominativoMasculino VARCHAR(100) NOT NULL,
    NominativoFeminino VARCHAR(100) NOT NULL,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_secoes_designacao (Designacao)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- ASSOCIADOS
-- Dados específicos de um associado. O nome pertence à entidade PESSOAS.
-- ============================================================================

CREATE TABLE associados (
    Id INT NOT NULL AUTO_INCREMENT,
    IdPessoa INT NOT NULL,
    NumeroAssociado VARCHAR(20) NULL,
    DataNascimento DATE NOT NULL,
    Genero CHAR(1) NOT NULL,
    IdNacionalidade INT NULL,
    IdEstadoCivil INT NULL,
    IdConfissaoReligiosa INT NULL,
    IdTipoDocumentoIdentificacao INT NULL,
    NumeroDocumentoIdentificacao VARCHAR(50) NULL,
    NumeroCartaoUtente CHAR(9) NULL,
    NominativoOutro VARCHAR(100) NULL,
    NomePai VARCHAR(150) NULL,
    NomeMae VARCHAR(150) NULL,
    DataInscricao DATE NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_associados_pessoa (IdPessoa),
    UNIQUE KEY uk_associados_numero (NumeroAssociado),
    KEY ix_associados_nacionalidade (IdNacionalidade),
    KEY ix_associados_estado_civil (IdEstadoCivil),
    KEY ix_associados_confissao (IdConfissaoReligiosa),
    KEY ix_associados_documento (IdTipoDocumentoIdentificacao),
    CONSTRAINT fk_associados_pessoa
        FOREIGN KEY (IdPessoa) REFERENCES pessoas(Id),
    CONSTRAINT fk_associados_nacionalidade
        FOREIGN KEY (IdNacionalidade) REFERENCES nacionalidades(Id),
    CONSTRAINT fk_associados_estado_civil
        FOREIGN KEY (IdEstadoCivil) REFERENCES estados_civis(Id),
    CONSTRAINT fk_associados_confissao
        FOREIGN KEY (IdConfissaoReligiosa) REFERENCES confissoes_religiosas(Id),
    CONSTRAINT fk_associados_documento
        FOREIGN KEY (IdTipoDocumentoIdentificacao)
        REFERENCES tipos_documento_identificacao(Id),
    CONSTRAINT chk_associados_genero
        CHECK (Genero IN ('M','F','O')),
    CONSTRAINT chk_associados_nominativo_outro
        CHECK ((Genero = 'O' AND NominativoOutro IS NOT NULL AND CHAR_LENGTH(TRIM(NominativoOutro)) > 0) OR (Genero IN ('M','F') AND NominativoOutro IS NULL)),
    CONSTRAINT chk_associados_cartao_utente
        CHECK (
            NumeroCartaoUtente IS NULL
            OR NumeroCartaoUtente REGEXP '^[0-9]{9}$'
        )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- HISTÓRICO DE SECÇÕES
-- ============================================================================

CREATE TABLE associados_secoes (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    IdSecao INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    KEY ix_associados_secoes_associado (IdAssociado),
    KEY ix_associados_secoes_secao (IdSecao),
    CONSTRAINT fk_associados_secoes_associado
        FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_associados_secoes_secao
        FOREIGN KEY (IdSecao) REFERENCES secoes(Id),
    CONSTRAINT chk_associados_secoes_datas
        CHECK (DataFim IS NULL OR DataFim >= DataInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- HISTÓRICO DE COMPANHIAS
-- Um associado pode não ter companhia.
-- ============================================================================

CREATE TABLE associados_companhias (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    IdCompanhia INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    KEY ix_associados_companhias_associado (IdAssociado),
    KEY ix_associados_companhias_companhia (IdCompanhia),
    CONSTRAINT fk_associados_companhias_associado
        FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_associados_companhias_companhia
        FOREIGN KEY (IdCompanhia) REFERENCES companhias(Id),
    CONSTRAINT chk_associados_companhias_datas
        CHECK (DataFim IS NULL OR DataFim >= DataInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- ENCARREGADOS DE EDUCAÇÃO
-- Uma pessoa desempenha este papel relativamente a um associado.
-- Mantém histórico.
-- ============================================================================

CREATE TABLE associados_encarregados_educacao (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    IdPessoa INT NOT NULL,
    IdTipoRelacao INT NOT NULL,
    DataInicio DATE NOT NULL,
    DataFim DATE NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    KEY ix_ae_associado (IdAssociado),
    KEY ix_ae_pessoa (IdPessoa),
    KEY ix_ae_relacao (IdTipoRelacao),
    CONSTRAINT fk_ae_associado
        FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_ae_pessoa
        FOREIGN KEY (IdPessoa) REFERENCES pessoas(Id),
    CONSTRAINT fk_ae_relacao
        FOREIGN KEY (IdTipoRelacao) REFERENCES tipos_relacao(Id),
    CONSTRAINT chk_ae_datas
        CHECK (DataFim IS NULL OR DataFim >= DataInicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- CONTACTOS DE EMERGÊNCIA
-- Opção B: entidade pessoa separada da relação com o associado.
-- Uma pessoa pode ser contacto de emergência de vários associados.
-- ============================================================================

-- Regra 17.1: um contacto de emergência não depende obrigatoriamente de um
-- registo próprio em "pessoas" (pode ser um amigo, vizinho, etc. sem
-- qualquer outra relação com o SIGA). Por isso guarda o Nome e o Contacto
-- directamente, em vez de uma FK obrigatória para pessoas.
CREATE TABLE associados_contactos_emergencia (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    Nome VARCHAR(150) NOT NULL,
    Contacto VARCHAR(50) NULL,
    IdTipoRelacao INT NOT NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    KEY ix_ace_relacao (IdTipoRelacao),
    CONSTRAINT fk_ace_associado
        FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_ace_relacao
        FOREIGN KEY (IdTipoRelacao) REFERENCES tipos_relacao(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- EVENTOS DOS ASSOCIADOS
-- Os eventos nunca são eliminados e permanecem mesmo após desactivação.
-- ============================================================================

CREATE TABLE eventos_associados (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    IdTipoEvento INT NOT NULL,
    DataEvento DATE NOT NULL,
    Observacoes TEXT NULL,
    PRIMARY KEY (Id),
    KEY ix_eventos_associados_associado (IdAssociado),
    KEY ix_eventos_associados_tipo (IdTipoEvento),
    KEY ix_eventos_associados_data (DataEvento),
    CONSTRAINT fk_eventos_associados_associado
        FOREIGN KEY (IdAssociado) REFERENCES associados(Id),
    CONSTRAINT fk_eventos_associados_tipo
        FOREIGN KEY (IdTipoEvento) REFERENCES tipos_evento(Id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- ============================================================================
-- DADOS INICIAIS
-- ============================================================================

INSERT INTO secoes
    (Designacao, NominativoMasculino, NominativoFeminino)
VALUES
    ('Colónia', 'Castor', 'Castora'),
    ('Alcateia', 'Lobito', 'Lobita'),
    ('Tribo Júnior', 'Escoteiro Júnior', 'Escoteira Júnior'),
    ('Tribo Sénior', 'Escoteiro Sénior', 'Escoteira Sénior'),
    ('Clã', 'Caminheiro', 'Caminheira'),
    ('Chefia', 'Escoteiro Chefe', 'Escoteira Chefe');

INSERT INTO tipos_relacao (Designacao)
VALUES
    ('Pai'),
    ('Mãe'),
    ('Padrasto'),
    ('Madrasta'),
    ('Avô'),
    ('Avó'),
    ('Irmão'),
    ('Irmã'),
    ('Tio'),
    ('Tia'),
    ('Primo'),
    ('Prima'),
    ('Cônjuge'),
    ('Tutor legal'),
    ('Amigo'),
    ('Vizinho'),
    ('Colega'),
    ('Outro');

INSERT INTO estados_civis (Designacao)
VALUES
    ('Solteiro'),
    ('Casado'),
    ('Divorciado'),
    ('Viúvo'),
    ('União de facto');

INSERT INTO tipos_documento_identificacao (Designacao)
VALUES
    ('Cartão de Cidadão'),
    ('Bilhete de Identidade'),
    ('Passaporte'),
    ('Autorização de Residência'),
    ('Outro');

-- Tipos de contacto mínimos; podem ser ampliados no backoffice.
INSERT INTO tipos_contacto (Designacao)
VALUES
    ('Telemóvel'),
    ('Telefone'),
    ('Email'),
    ('Email Associativo');

-- Companhia de âmbito nacional, protegida da alteração estrutural.
INSERT INTO companhias (Designacao, ambito_global, Activo)
VALUES ('Chefia Nacional', 1, 1);

-- Nacionalidades internacionais (lista inicial; gestão posterior no backoffice).
INSERT INTO nacionalidades (Nacionalidade)
VALUES
    ('Afegã'),
    ('Albanesa'),
    ('Alemã'),
    ('Andorrana'),
    ('Angolana'),
    ('Antiguana'),
    ('Argelina'),
    ('Argentina'),
    ('Arménia'),
    ('Australiana'),
    ('Austríaca'),
    ('Azerbaijana'),
    ('Baamiana'),
    ('Bahamense'),
    ('Bangladechiana'),
    ('Barbadiana'),
    ('Bareinita'),
    ('Belga'),
    ('Belizenha'),
    ('Beninense'),
    ('Bielorrussa'),
    ('Birmanesa'),
    ('Boliviana'),
    ('Bósnia'),
    ('Botsuanesa'),
    ('Brasileira'),
    ('Bruneiana'),
    ('Búlgara'),
    ('Burquinabê'),
    ('Burundesa'),
    ('Butanesa'),
    ('Cabo-verdiana'),
    ('Camaronesa'),
    ('Cambojana'),
    ('Canadiana'),
    ('Catariana'),
    ('Cazaque'),
    ('Centro-Africana'),
    ('Chadiana'),
    ('Chilena'),
    ('Chinesa'),
    ('Cipriota'),
    ('Colombiana'),
    ('Comorense'),
    ('Congolesa'),
    ('Coreana do Norte'),
    ('Coreana do Sul'),
    ('Costa-marfinense'),
    ('Costa-riquenha'),
    ('Croata'),
    ('Cubana'),
    ('Dinamarquesa'),
    ('Djibutiana'),
    ('Dominicana'),
    ('Dominiquense'),
    ('Egípcia'),
    ('Salvadorenha'),
    ('Emiratense'),
    ('Equatoriana'),
    ('Eritreia'),
    ('Eslovaca'),
    ('Eslovena'),
    ('Espanhola'),
    ('Essuatiniana'),
    ('Estadunidense'),
    ('Estónia'),
    ('Etíope'),
    ('Fijiana'),
    ('Filipina'),
    ('Finlandesa'),
    ('Francesa'),
    ('Gabonesa'),
    ('Gambiana'),
    ('Ganesa'),
    ('Georgiana'),
    ('Granadina'),
    ('Grega'),
    ('Guatemalteca'),
    ('Guianense'),
    ('Guineense'),
    ('Guineense-Bissauense'),
    ('Guinéu-equatoriana'),
    ('Haitiana'),
    ('Hondurenha'),
    ('Húngara'),
    ('Iemenita'),
    ('Indiana'),
    ('Indonésia'),
    ('Iraniana'),
    ('Iraquiana'),
    ('Irlandesa'),
    ('Islandesa'),
    ('Israelita'),
    ('Italiana'),
    ('Jamaicana'),
    ('Japonesa'),
    ('Jordaniana'),
    ('Kiribatiana'),
    ('Kuwaitiana'),
    ('Laociana'),
    ('Lesotiana'),
    ('Letã'),
    ('Libanesa'),
    ('Liberiana'),
    ('Líbia'),
    ('Liechtensteinense'),
    ('Lituana'),
    ('Luxemburguesa'),
    ('Macedónia do Norte'),
    ('Madagascarense'),
    ('Malásia'),
    ('Malauiana'),
    ('Maldiva'),
    ('Malgaxe'),
    ('Malianesa'),
    ('Maltesa'),
    ('Marfinense'),
    ('Marroquina'),
    ('Marshallina'),
    ('Mauriciana'),
    ('Mauritana'),
    ('Mexicana'),
    ('Micronésia'),
    ('Moldava'),
    ('Monegasca'),
    ('Mongol'),
    ('Montenegrina'),
    ('Moçambicana'),
    ('Namibiana'),
    ('Nauruana'),
    ('Nepalesa'),
    ('Nicaraguense'),
    ('Nigeriana'),
    ('Nigerina'),
    ('Norueguesa'),
    ('Neozelandesa'),
    ('Omanense'),
    ('Paquistanesa'),
    ('Palauana'),
    ('Panamenha'),
    ('Papua'),
    ('Paraguaia'),
    ('Peruana'),
    ('Polaca'),
    ('Portuguesa'),
    ('Queniana'),
    ('Quirguiz'),
    ('Ruandesa'),
    ('Romena'),
    ('Russa'),
    ('Salomónica'),
    ('Samoana'),
    ('Santa-lucense'),
    ('São-cristovense'),
    ('São-marinense'),
    ('São-tomense'),
    ('São-vicentina'),
    ('Senegalesa'),
    ('Serra-leonesa'),
    ('Sérvia'),
    ('Seychellense'),
    ('Singapurense'),
    ('Síria'),
    ('Somali'),
    ('Sri-lankesa'),
    ('Sul-africana'),
    ('Sudanesa'),
    ('Sudanesa do Sul'),
    ('Sueca'),
    ('Suíça'),
    ('Surinamesa'),
    ('Tailandesa'),
    ('Taiwanesa'),
    ('Tajique'),
    ('Tanzaniana'),
    ('Tcheca'),
    ('Timorense'),
    ('Togolesa'),
    ('Tonganesa'),
    ('Trinitária'),
    ('Tunisina'),
    ('Turca'),
    ('Turcomena'),
    ('Tuvaluana'),
    ('Ucraniana'),
    ('Ugandesa'),
    ('Uruguaia'),
    ('Uzbeque'),
    ('Vanuatuense'),
    ('Vaticana'),
    ('Venezuelana'),
    ('Vietnamita'),
    ('Zambiana'),
    ('Zimbabuana');

-- Confissões religiosas e opções de identificação inicial; gestão posterior no backoffice.
INSERT INTO confissoes_religiosas (Designacao)
VALUES
    ('Católica'),
    ('Ortodoxa'),
    ('Protestante'),
    ('Anglicana'),
    ('Evangélica'),
    ('Muçulmana'),
    ('Judaica'),
    ('Hindu'),
    ('Budista'),
    ('Sikh'),
    ('Bahá’í'),
    ('Outra'),
    ('Sem religião'),
    ('Ateu'),
    ('Agnóstico');

-- Tipo base obrigatório para a criação de associados. Os restantes são geridos no backoffice.
INSERT INTO tipos_evento (Designacao) VALUES ('Admissão');
INSERT INTO tipos_evento (Designacao) VALUES ('Desactivação');
INSERT INTO tipos_evento (Designacao) VALUES ('Reactivação');



-- ============================================================================
-- TABELAS LEGADAS MANTIDAS NA V14
-- Necessárias à aplicação SIGA e ainda não substituídas pelo novo modelo.
-- ============================================================================

CREATE TABLE utilizadores (
    Id INT NOT NULL AUTO_INCREMENT,
    Nome VARCHAR(100) NOT NULL,
    Email VARCHAR(150) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Administrador TINYINT(1) NOT NULL DEFAULT 0,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (Id),
    UNIQUE KEY uk_utilizadores_nome (Nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE utilizadores_companhias (
    Id INT NOT NULL AUTO_INCREMENT,
    IdUtilizador INT NOT NULL,
    IdCompanhia INT NOT NULL,
    DataInicio DATETIME NOT NULL,
    DataFim DATETIME NULL,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    DataHora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (Id),
    KEY idx_uc_utilizador_actual (IdUtilizador, Activo, DataFim),
    KEY idx_uc_companhia_actual (IdCompanhia, Activo, DataFim),
    CONSTRAINT fk_uc_utilizador FOREIGN KEY (IdUtilizador) REFERENCES utilizadores(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_uc_companhia FOREIGN KEY (IdCompanhia) REFERENCES companhias(Id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE utilizadores_associados (
    IdUtilizador INT NOT NULL,
    IdAssociado INT NOT NULL,
    DataHora TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Activo TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (IdUtilizador),
    UNIQUE KEY uk_utilizadores_associados_associado (IdAssociado),
    CONSTRAINT fk_ua_utilizador FOREIGN KEY (IdUtilizador) REFERENCES utilizadores(Id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ua_associado FOREIGN KEY (IdAssociado) REFERENCES associados(Id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE consentimentos (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    DadosPessoais TINYINT(1) NOT NULL,
    DadosSaude TINYINT(1) NOT NULL,
    DadosVozImagem TINYINT(1) NOT NULL,
    PRIMARY KEY (Id),
    KEY ix_consentimentos_associado (IdAssociado),
    CONSTRAINT fk_consentimentos_associado FOREIGN KEY (IdAssociado) REFERENCES associados(Id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE fichas_saude (
    Id INT NOT NULL AUTO_INCREMENT,
    IdAssociado INT NOT NULL,
    NumUente CHAR(9) NOT NULL,
    Asma TINYINT(1) NOT NULL DEFAULT 0,
    Epilepsia TINYINT(1) NOT NULL DEFAULT 0,
    Diabetes TINYINT(1) NOT NULL DEFAULT 0,
    Alergias TINYINT(1) NOT NULL DEFAULT 0,
    DescAlergias TEXT NULL,
    MedicacaoRegular TEXT NULL,
    RestricoesAlimentares TEXT NULL,
    Outros TEXT NULL,
    PRIMARY KEY (Id),
    KEY ix_fichas_saude_associado (IdAssociado),
    CONSTRAINT fk_fichas_saude_associado FOREIGN KEY (IdAssociado) REFERENCES associados(Id) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

CREATE TABLE fichas_saude_historico (
    Id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    IdFichaSaude INT NOT NULL,
    IdAssociado INT NOT NULL,
    IdUtilizador INT NOT NULL,
    DataHora DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    Operacao VARCHAR(20) NOT NULL,
    DadosAnteriores LONGTEXT NULL,
    DadosNovos LONGTEXT NULL,
    PRIMARY KEY (Id),
    KEY idx_fsh_associado_data (IdAssociado, DataHora),
    KEY idx_fsh_utilizador_data (IdUtilizador, DataHora),
    CONSTRAINT fk_fsh_ficha FOREIGN KEY (IdFichaSaude) REFERENCES fichas_saude(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_fsh_associado FOREIGN KEY (IdAssociado) REFERENCES associados(Id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT fk_fsh_utilizador FOREIGN KEY (IdUtilizador) REFERENCES utilizadores(Id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_uca1400_as_ci;

-- Utilizador inicial necessário para o primeiro login.
-- Password inicial: admin@ueo2026 (deve ser alterada após o primeiro acesso).
INSERT INTO utilizadores (Nome, Email, Password, Administrador, Activo)
SELECT 'Administrador', '', '$2b$12$DGMSTqxWHxuq6sEA0ThBC.Ed6zbQHBL1L0ywuc5ArCxg0I9/Ruqki', 1, 1
WHERE NOT EXISTS (
    SELECT 1 FROM utilizadores WHERE Nome = 'Administrador'
);

-- ============================================================================
-- FIM DO SCRIPT
-- ============================================================================
