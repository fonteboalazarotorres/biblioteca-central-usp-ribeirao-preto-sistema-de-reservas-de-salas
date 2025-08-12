$(document).ready(function() {
    // Atualiza disponibilidade quando algum campo relevante muda
    $('#data_reserva, #hora_entrada, #hora_saida, #quantidade_pessoas').on('change', function() {
        verificarDisponibilidade();
    });

    // Verifica disponibilidade quando a página carrega se form estiver preenchido
    if ($('#data_reserva').val() && $('#hora_entrada').val() && $('#hora_saida').val() && $('#quantidade_pessoas').val()) {
        verificarDisponibilidade();
    }

    // Validação antes do envio
    $('#formReserva').on('submit', function(e) {
        let sala_id_checked = $("input[name='sala_id']:checked").length || $("select[name='sala_id']").val();

        if (!sala_id_checked) {
            e.preventDefault();
            alert('Por favor, selecione uma sala disponível.');
            return false;
        }

        var horaEntrada = $('#hora_entrada').val();
        var horaSaida = $('#hora_saida').val();

        if (!horaEntrada || !horaSaida || horaEntrada >= horaSaida) {
            e.preventDefault();
            alert('A hora de saída deve ser posterior à hora de entrada.');
            return false;
        }

        // Se quiser: desabilite botão enviar aqui para evitar duplo submit
        // $('#btnReservar').prop('disabled', true);
        // return true;
    });

    // Função AJAX de disponibilidade
    function verificarDisponibilidade() {
        var data = $('#data_reserva').val() || '';
        var horaEntrada = $('#hora_entrada').val() || '';
        var horaSaida = $('#hora_saida').val() || '';
        var qtdPessoas = $('#quantidade_pessoas').val() || '';

        if (data && horaEntrada && horaSaida && qtdPessoas) {
            $.ajax({
                url: 'verificar_disponibilidade.php',
                type: 'POST',
                data: {
                    data: data,
                    hora_entrada: horaEntrada,
                    hora_saida: horaSaida,
                    quantidade_pessoas: qtdPessoas
                },
                dataType: 'json',
                beforeSend: function() {
                    $('#salas_container').html('<div class="col-12 text-center"><p>Carregando salas disponíveis...</p></div>');
                },
                success: function(response) {
                    var html = '';
                    if (response.status === 'success') {
                        if (response.salas && response.salas.length > 0) {
                            $.each(response.salas, function(index, sala) {
                                html += '<div class="col-md-4 mb-3">';
                                html += '<div class="card h-100">';
                                html += '<div class="card-body">';
                                html += '<div class="form-check">';
                                html += '<input class="form-check-input" type="radio" name="sala_id" value="' + sala.id + '" id="sala_' + sala.id + '">';
                                html += '<label class="form-check-label" for="sala_' + sala.id + '">';
                                html += '<strong>' + sala.nome + '</strong>' + (sala.descricao ? ' (' + sala.descricao + ')' : '');
                                html += '</label>';
                                html += '</div></div></div></div>';
                            });
                        } else {
                            html = '<div class="col-12 text-center"><p class="text-danger">Não há salas disponíveis para o horário selecionado.</p></div>';
                        }
                    } else {
                        html = '<div class="col-12 text-center"><p class="text-danger">' + (response.message ? response.message : 'Erro ao buscar salas disponíveis.') + '</p></div>';
                    }
                    $('#salas_container').html(html);
                },
                error: function(xhr, status, error) {
                    let msg = 'Erro ao verificar disponibilidade. Tente novamente.';
                    try {
                        var response = JSON.parse(xhr.responseText);
                        if (response && response.message) msg = response.message;
                    } catch(e){}
                    $('#salas_container').html('<div class="col-12 text-center"><p class="text-danger">' + msg + '</p></div>');
                    console.error('Disponibilidade AJAX error:', status, error);
                }
            });
        }
    }
});