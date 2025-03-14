<?php
require_once('./componentes/principal/backprincipal.php');

// Verifica se há uma requisição POST para atualização
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    // Exiba os dados recebidos para debug
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    $num_certidao = $_POST['num_certidao'];
    $data_inicial_obj = DateTime::createFromFormat('d/m/Y H:i', $_POST['data_inicial']);
    $data_final_obj = DateTime::createFromFormat('d/m/Y H:i', $_POST['data_final']);

    if ($data_inicial_obj && $data_final_obj) {
        $data_inicial = $data_inicial_obj->format('Y-m-d H:i:s');
        $data_final = $data_final_obj->format('Y-m-d H:i:s');

        $tipo = $_POST['tipo'];
        $origem = $_POST['origem'];
        $quantidade = $_POST['quantidade'];

        // Chama a função de atualização e armazena o resultado
        $resultado = atualizarCertidao($num_certidao, $data_inicial, $data_final, $origem, $tipo, $quantidade);

        if ($resultado) {
            echo "<script>alert('Certidão atualizada com sucesso!'); window.location.href = '".$_SERVER['PHP_SELF']."';</script>";
        } else {
            echo "<script>alert('Erro ao atualizar a certidão. Por favor, tente novamente.');</script>";
        }
    } else {
        echo "<script>alert('Erro: Formato de data inválido. Verifique as datas inseridas.');</script>";
    }
}

if (isset($_GET['delete_id'])) {
    $num_certidao = $_GET['delete_id'];
    deletarCertidao($num_certidao);
}

// Obtém a página atual (padrão para 1 se não especificado)
$paginaAtual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$limite = 35; // Número de certidões por página
$offset = ($paginaAtual - 1) * $limite;

// Filtra as certidões com paginação
$filtro_num_certidao = isset($_GET['filtro_num_certidao']) ? $_GET['filtro_num_certidao'] : null;
$data_inicial = null;
$data_final = null;

// Total de certidões para paginação
$certidoes = filtrarCertidoes($filtro_num_certidao, $limite, $offset);

if ($filtro_num_certidao) {
    $certidoes = filtrarCertidoes($filtro_num_certidao, $limite, $offset);
    // Recalcular o total de certidões filtradas pelo número da certidão
} else {
    // Caso contrário, utiliza o mês atual para filtrar
    $certidoes = filtrarCertidoes($filtro_num_certidao, $limite, $offset);
    // Recalcular o total de certidões com base no mês atual

}

?>

<link rel="stylesheet" href="./componentes/principal/protocolos/protocolos.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>

<div class="protocolos">
    <h3>Certidões</h3>
    <table>
        <tr>
            <th>Número</th>
            <th>Data de Início</th>
            <th>Data de Fim</th>
            <th>Intervalo</th>
            <th>Tipo</th>
            <th>Origem</th>
            <th>Ações</th>
        </tr>
        <?php if (!empty($certidoes)): ?>
            <?php foreach ($certidoes as $certidao): ?>
                <tr>
                    <td><?php echo htmlspecialchars($certidao['num']); ?></td>
                    <td>
                        <?php 
                        $dataInicialObj = DateTime::createFromFormat('Y-m-d H:i:s', $certidao['data_inicial']);
                        echo $dataInicialObj ? $dataInicialObj->format('d/m/Y H:i') : 'Data inválida';
                        ?>
                    </td>
                    <td>
                        <?php 
                        $dataFinalObj = DateTime::createFromFormat('Y-m-d H:i:s', $certidao['data_final']);
                        echo $dataFinalObj ? $dataFinalObj->format('d/m/Y H:i') : 'Data inválida';
                        ?>
                    </td>
                    <td>
                    <?php
                    $intervalo_em_horas = $certidao['intervalo'];

                    // Divide o intervalo em dias e horas
                    $dias = floor($intervalo_em_horas / 24); // Calcula a quantidade de dias
                    $horas_restantes = $intervalo_em_horas - ($dias * 24); // Calcula as horas restantes
                    $horas = floor($horas_restantes);
                    $minutos = round(($horas_restantes - $horas) * 60);

                    echo $dias . " dia" . ($dias != 1 ? "s" : "") . " " . $horas . " hora" . ($horas != 1 ? "s" : "") . " e " . $minutos . " minuto" . ($minutos != 1 ? "s" : "");
                    ?>
                    </td>
                    <td><?php echo htmlspecialchars($certidao['tipo']); ?></td>
                    <td><?php echo htmlspecialchars($certidao['origem']); ?></td>
                    <td>
                        <button type="button" class="bt-editar" data-id="<?php echo htmlspecialchars($certidao['num']); ?>">Editar</button>
                        <a href="<?php echo $_SERVER['PHP_SELF'] . '?delete_id=' . htmlspecialchars($certidao['num']); ?>" onclick="return confirm('Tem certeza que deseja excluir esta certidão?');" class="btn-delete">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="7">Nenhuma certidão encontrada.</td>
                <?php
                if (isset($_GET['filtro_num_certidao'])) {
                    echo 'Filtro de num_certidao: ' . $_GET['filtro_num_certidao'];
                }
                                
                ?>
            </tr>
        <?php endif; ?>
    </table>
</div>

    <!-- Modal de Edição -->
    <div id="modal-editar" class="modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center;">
        <div class="modal-content" style="background-color: #fff; padding: 20px; border-radius: 8px; width: 500px; max-width: 90%;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h5 style="margin: 0;">Editar Certidão</h5>
                <button type="button" class="close" onclick="fecharModal()" style="background: none; border: none; font-size: 24px;">&times;</button>
            </div>
            <div class="modal-body">
                <form id="form-editar" method="post">
                    <input type="hidden" name="action" value="update">

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="modal-num_certidao">Número da Certidão:</label>
                        <input type="text" class="form-control" name="num_certidao" id="modal-num_certidao" readonly>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="modal-data_inicial">Data Inicial:</label>
                        <input type="text" class="form-control" name="data_inicial" id="modal-data_inicial" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="modal-data_final">Data Final:</label>
                        <input type="text" class="form-control" name="data_final" id="modal-data_final" required>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="modal-tipo">Tipo de Certidão:</label>
                        <select name="tipo" id="modal-tipo" class="form-control" required>
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
                            <option value="Certidão de documento arquivado">Certidão de documento arquivado</option>
                            <option value="Consulta de penhor">Consulta de penhor</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="modal-origem">Origem:</label>
                        <select name="origem" id="modal-origem" class="form-control" required>
                            <option value="ONR">ONR</option>
                            <option value="Cartórios Maranhão">Cartórios Maranhão</option>
                            <option value="Presencial">Presencial</option>
                            <option value="Cliente na recepção">Cliente na recepção</option>
                            <option value="Imob">Imob</option>
                            <option value="Email">Email</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 15px;">
                        <label for="modal-quantidade">Quantidade:</label>
                        <input type="number" class="form-control" name="quantidade" id="modal-quantidade" required>
                    </div>

                    <div class="modal-footer" style="display: flex; justify-content: flex-end; margin-top: 20px;">
                        <button type="button" onclick="fecharModal()" style="padding: 10px 20px; border: none; background-color: #ccc; border-radius: 4px; cursor: pointer; margin-right: 10px;">Cancelar</button>
                        <button type="submit" class="btn-save" style="padding: 10px 20px; border: none; background-color: #007bff; color: #fff; border-radius: 4px; cursor: pointer;">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Aplicar a máscara para os campos de data e hora no modal
        $('#modal-data_inicial, #modal-data_final, #data_filtro').mask('00/00/0000 00:00', {
            placeholder: "dd/mm/aaaa hh:mm"
        });

        // Preenchendo o modal com os dados da tabela
        document.querySelectorAll('.bt-editar').forEach(button => {
            button.addEventListener('click', function() {
                const row = this.closest('tr');
                const id = this.getAttribute('data-id');

                document.getElementById('modal-num_certidao').value = row.children[0].textContent.trim();
                document.getElementById('modal-data_inicial').value = row.children[1].textContent.trim();
                document.getElementById('modal-data_final').value = row.children[2].textContent.trim(); // Ajuste conforme necessário
                document.getElementById('modal-tipo').value = row.children[3].textContent.trim();
                document.getElementById('modal-origem').value = row.children[4].textContent.trim();
                document.getElementById('modal-quantidade').value = row.children[5] ? row.children[5].textContent.trim() : '';

                document.getElementById('modal-editar').style.display = 'flex';
            });
        });
    });

    function fecharModal() {
        document.getElementById('modal-editar').style.display = 'none';
    }
    </script>
</div>
