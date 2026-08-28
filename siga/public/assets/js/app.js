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
};
