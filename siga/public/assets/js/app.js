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
            this.actualizarObrigatoriedadeEmailAssociativo(selectSecao);
        }

        const checkboxInsignia = document.getElementById('InsigniaMadeira');
        if (checkboxInsignia) {
            this.actualizarObrigatoriedadeDataInsignia(checkboxInsignia);
        }
    },

    /**
     * Regra 27: o email associativo é obrigatório para associados na secção
     * "Chefia" (dirigentes). Torna o campo obrigatório e destaca a ajuda
     * quando essa secção é seleccionada.
     */
    actualizarObrigatoriedadeEmailAssociativo(selectSecao) {
        const campoEmail = document.getElementById('EmailAssociativo');
        const ajuda = document.getElementById('ajudaEmailAssociativo');
        if (!campoEmail) return;

        const opcaoSeleccionada = selectSecao.options[selectSecao.selectedIndex];
        const eChefia = opcaoSeleccionada && opcaoSeleccionada.dataset.designacao === 'Chefia';

        campoEmail.required = eChefia;
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

