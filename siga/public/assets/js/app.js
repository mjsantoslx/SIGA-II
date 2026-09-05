/**
 * SIGA — pequenas interações do lado do cliente.
 * Sem dependências externas.
 */
window.siga = {
    /**
     * Adiciona uma nova linha repetível (ex.: encarregados de educação,
     * contactos de emergência) clonando a última linha existente.
     */
    adicionarLinha(idContentor, ...nomesCampos) {
        const contentor = document.getElementById(idContentor);
        if (!contentor) return;

        const ultimaLinha = contentor.querySelector('.linha-repetivel:last-child');
        const novaLinha = ultimaLinha.cloneNode(true);

        novaLinha.querySelectorAll('input, select').forEach((campo) => {
            if (campo.tagName === 'SELECT') {
                campo.selectedIndex = 0;
            } else {
                campo.value = '';
            }
        });

        contentor.appendChild(novaLinha);
    },

    /**
     * Máscara de datas dd/mm/aaaa: o utilizador escreve só algarismos e as
     * barras são inseridas automaticamente (regra 8.3 das regras de negócio).
     */
    aplicarMascaraData(campo) {
        campo.addEventListener('input', () => {
            let digitos = campo.value.replace(/\D/g, '').slice(0, 8);
            let resultado = digitos;
            if (digitos.length > 4) {
                resultado = `${digitos.slice(0, 2)}/${digitos.slice(2, 4)}/${digitos.slice(4)}`;
            } else if (digitos.length > 2) {
                resultado = `${digitos.slice(0, 2)}/${digitos.slice(2)}`;
            }
            campo.value = resultado;
        });
    },

    iniciar() {
        document.querySelectorAll('.campo-data').forEach((campo) => this.aplicarMascaraData(campo));

        const selectSecao = document.getElementById('IdSecao');
        if (selectSecao) {
            this.actualizarDependenciasSeccao(selectSecao);
        }

        const checkboxInsignia = document.getElementById('InsigniaMadeira');
        if (checkboxInsignia) {
            this.actualizarObrigatoriedadeDataInsignia(checkboxInsignia);
        }
    },

    /**
     * Regra 27/36/39: reúne todas as dependências da secção escolhida no
     * formulário de associado — email associativo, o bloco inteiro de
     * dirigente (Chefia Nacional, órgãos, cargos, formador, insígnia de
     * madeira) e o cargo "Equipa Nacional de Clã" (exclusivo do Clã).
     * Esconde e limpa cada bloco quando a secção escolhida não se aplica,
     * para não sobrar uma selecção escondida a ser submetida por engano.
     */
    actualizarDependenciasSeccao(selectSecao) {
        this.actualizarObrigatoriedadeEmailAssociativo(selectSecao);

        const opcaoSeleccionada = selectSecao.options[selectSecao.selectedIndex];
        const designacao = opcaoSeleccionada ? opcaoSeleccionada.dataset.designacao : '';

        const grupoDirigente = document.getElementById('grupo-dirigente');
        if (grupoDirigente) {
            const eChefia = designacao === 'Chefia';
            grupoDirigente.style.display = eChefia ? 'block' : 'none';
            if (!eChefia) {
                grupoDirigente.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = false; });
                const campoDataInsignia = document.getElementById('DataInsigniaMadeira');
                if (campoDataInsignia) campoDataInsignia.value = '';
                const grupoDataInsignia = document.getElementById('grupo-data-insignia');
                if (grupoDataInsignia) grupoDataInsignia.style.display = 'none';
            }
        }

        const grupoCla = document.getElementById('grupo-cla');
        if (grupoCla) {
            const eCla = designacao === 'Clã';
            grupoCla.style.display = eCla ? 'block' : 'none';
            if (!eCla) {
                grupoCla.querySelectorAll('input[type="checkbox"]').forEach((cb) => { cb.checked = false; });
            }
        }
    },

    /**
     * Regra 27/36: o email associativo é obrigatório para associados na
     * secção "Chefia" (dirigentes), e só pode ser preenchido nesse caso —
     * por isso o campo fica desactivado (e é limpo) fora da secção "Chefia".
     */
    actualizarObrigatoriedadeEmailAssociativo(selectSecao) {
        const campoEmail = document.getElementById('EmailAssociativo');
        const ajuda = document.getElementById('ajudaEmailAssociativo');
        if (!campoEmail) return;

        const opcaoSeleccionada = selectSecao.options[selectSecao.selectedIndex];
        const eChefia = opcaoSeleccionada && opcaoSeleccionada.dataset.designacao === 'Chefia';

        campoEmail.required = eChefia;
        campoEmail.disabled = !eChefia;
        if (!eChefia) {
            campoEmail.value = '';
        }
        if (ajuda) {
            ajuda.style.color = eChefia ? 'var(--fogueira)' : '';
            ajuda.style.fontWeight = eChefia ? '600' : '';
        }
    },

    /**
     * Mostra/exige a data de atribuição apenas quando "Tem insígnia de
     * madeira" está marcado.
     */
    actualizarObrigatoriedadeDataInsignia(checkbox) {
        const grupo = document.getElementById('grupo-data-insignia');
        const campoData = document.getElementById('DataInsigniaMadeira');
        if (!grupo || !campoData) return;

        grupo.style.display = checkbox.checked ? 'block' : 'none';
        campoData.required = checkbox.checked;
        if (!checkbox.checked) {
            campoData.value = '';
        }
    },
};

document.addEventListener('DOMContentLoaded', () => window.siga.iniciar());

