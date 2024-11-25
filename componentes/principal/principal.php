<link rel="stylesheet" href="./componentes/principal/principal.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
<?php 
require('./componentes/principal/backprincipal.php');

// Verifica se o formulário foi enviado
if (isset($_POST['action']) && $_POST['action'] == 'insert') {
    // Extrai os dados do formulário
    $num_certidao = (int) $_POST['num_certidao'];
    $data_inicial = $_POST['data_inicial'];
    $data_final = $_POST['data_final'];
    $origem = $_POST['origem']; // Agora este campo deve estar definido
    $tipo = $_POST['tipo']; // Agora este campo deve estar definido
    $quantidade = (int) $_POST['quantidade'];

    // Chama a função para inserir os dados no banco
    calcularEInserirCertidao($num_certidao, $data_inicial, $data_final, $origem, $tipo, $quantidade);
}
?>

<div class="content">
    <h1>Bem-vindo!</h1>
    <div class="input-area">
        <form method="post" id="form-insert">
            <input type="hidden" name="action" value="insert">
            <div class="input-data">
                <p class="text-input">Número de certidão:</p>
                <input type="text" name="num_certidao" class="input-data-box" id="num_certidao">
            </div>

            <div class="input-data">
                <p class="text-input">Origem:</p>
                <select name="origem" class="input-data-select" required>
                    <option value="ONR">ONR</option>
                    <option value="Cartórios Maranhão">Cartórios Maranhão</option>
                    <option value="Presencial">Presencial</option>
                    <option value="Cliente na recepção">Cliente na recepção</option>
                    <option value="Imob">Imob</option>
                    <option value="Email">Email</option>
                </select>
            </div>

            <div class="input-data">
                <p class="text-input">Quantidade:</p>
                <input type="number" name="quantidade" class="input-data-box" id="quantidade">
            </div>

            <div class="input-data">
                <p class="text-input">Tipo de certidão:</p>
                <select name="tipo" class="input-data-select" required>
                    <option value="Busca">Busca</option>
                    <option value="Situação jurídica">Situação jurídica</option>
                    <option value="Inteiro Teor">Inteiro Teor</option>
                    <option value="Inteiro Teor + Situação jurídica">Inteiro Teor + Situação jurídica</option>
                    <option value="Inteiro teor + Reipersecutória + Consulta de propriedade">Inteiro Teor + Reipersecutória + Consulta de propriedade</option>
                    <option value="Inteiro Teor + Ônus + Reipersecutória">Inteiro Teor + Ônus + Reipersecutória</option>
                    <option value="Inteiro Teor + Ônus">Inteiro Teor + Ônus</option>
                    <option value="Vitenária">Vitenária</option>
                    <option value="Ônus">Ônus</option>
                    <option value="Consulta de registro">Consulta de registro</option>
                    <option value="Consulta de propriedade">Consulta de propriedade</option>
                    <option value="Certidão negativa de registro auxiliar">Certidão negativa de registro auxiliar</option>
                    <option value="Certidão em relatório">Certidão em relatório</option>
                    <option value="Inteiro teor de registro auxiliar">Inteiro teor de registro auxiliar</option>
                </select>
            </div>

            <div class="input-data">
                <p class="text-input">Data inicial:</p>
                <input type="text" name="data_inicial" class="input-data-box" id="data_inicial">
            </div>
            <div class="input-data">
                <p class="text-input">Data final:</p>
                <input type="text" name="data_final" class="input-data-box" id="data_final">
            </div>

            <button type="submit" class="bt-insert">Adicionar certidão</button>

        </form>
    </div>
    <?php
    require('./componentes/principal/protocolos/protocolos.php')
    
    ?>

    <script>
        $(document).ready(function(){
            // Atualizar a máscara para dd/mm/yyyy hh:mm
            $('#data_inicial, #data_final').mask('00/00/0000 00:00');

            // Função de validação de data e hora
            $('#data_inicial, #data_final').on('blur', function() {
                let valor = $(this).val();
                let [data, hora] = valor.split(' ');
                if (!hora) hora = '08:00'; // Valor padrão se hora não for fornecida
                let [dia, mes, ano] = data.split('/');
                let [horas, minutos] = hora.split(':');
                let dataAtual = new Date();
                let anoAtual = dataAtual.getFullYear();

                dia = parseInt(dia) > 31 ? '31' : dia.padStart(2, '0');
                mes = parseInt(mes) > 12 ? '12' : mes.padStart(2, '0');
                ano = ano.length < 4 ? anoAtual.toString().slice(-4) : ano;
                horas = parseInt(horas) < 8 ? '08' : (parseInt(horas) > 17 ? '17' : horas.padStart(2, '0'));

                // Assegurar que o ano tem 4 dígitos
                if (ano.length < 4) {
                    ano = anoAtual.toString().slice(-4);
                }

                $(this).val(`${dia.padStart(2, '0')}/${mes.padStart(2, '0')}/${ano} ${horas}:${minutos}`);
            });

            // Restringir entrada apenas a números nos campos específicos
            $('#num_certidao, #quantidade').on('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
            });

        });
    </script>