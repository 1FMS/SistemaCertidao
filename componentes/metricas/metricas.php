<?php
include_once('./bd.php');
include_once('./componentes/metricas/backmetricas.php');

// Inicializar variáveis para mensagens e métricas
$mensagem = '';
$tipo_mensagem = '';
$mes = '';
$certidoes = [];
$certidoesOrigem = '';
$certidaoEditar = [];
$abrirModalCertidoes = false;
$abrirModalEditar = false;

// Filtrar métricas por mês
if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($_GET['mes_metricas']) && is_numeric($_GET['mes_metricas']) && $_GET['mes_metricas'] >= 1 && $_GET['mes_metricas'] <= 12) {
        $mes = $_GET['mes_metricas'];
    }
}

// Obter as métricas com ou sem filtro
$metricas = obterMetricas($conn, $mes);
$tempo_medio_total = calcularTempoMedioTotal($conn, $mes);
$tempo_medio_por_tipo = calcularTempoMedioPorTipoCertidao($conn, $mes);

// Verificar se foi solicitado para abrir o modal de certidões
if (isset($_GET['origem']) && in_array($_GET['origem'], ['ONR', 'CM', 'Presencial', 'Email'])) {
    $certidoesOrigem = $_GET['origem'];
    $certidoes = obterCertidoesPorOrigem($conn, $certidoesOrigem, $mes);
    $abrirModalCertidoes = true;
}

// Verificar se foi solicitado para abrir o modal de edição
if (isset($_GET['edit_id'])) {
    $edit_id = intval($_GET['edit_id']);
    if ($edit_id > 0) {
        $certidaoEditar = obterCertidaoPorId($conn, $edit_id);
        $abrirModalEditar = true;
    }
}
$total_certidoes = calcularTotalCertidoesNoMes($conn, $mes);
$media_certidoes = calcularMediaCertidoesNoMes($conn, $mes);
$tempo_medio_recepcao = calcularTempoMedioClienteRecepcao($conn, $mes);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Métricas</title>
    <link rel="stylesheet" href="./componentes/metricas/metricas.css">
</head>
<body>
    <header>
        <h1>Métricas</h1>
    </header>

    <section class="filtro">
        <form method="get">
            <input type="hidden" name="pagina" value="metricas">
            <label for="mes">Mês:</label>
            <select id="mes" name="mes_metricas">
                <option value="" disabled <?php echo ($mes == '') ? 'selected' : ''; ?>>Selecione o Mês</option>
                <?php
                    $meses = [
                        1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
                        5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
                        9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
                    ];
                    foreach ($meses as $numero => $nome) {
                        $selected = ($mes == $numero) ? 'selected' : '';
                        echo "<option value='$numero' $selected>$nome</option>";
                    }
                ?>
            </select>
            <button type="submit">Filtrar</button>
            <a href="index.php?pagina=metricas">Resetar</a>
        </form>
    </section>

    <?php if ($mensagem): ?>
        <section class="mensagem <?php echo htmlspecialchars($tipo_mensagem); ?>">
            <?php echo htmlspecialchars($mensagem); ?>
        </section>
    <?php endif; ?>

    <section class="metricas">
        <?php
            $metricasExibir = [
                'Total de Pedidos' => 'total-pedidos',
                'ONR' => 'onr',
                'Cartórios Maranhão' => 'cm',
                'Email' => 'email',
                'Presencial' => 'Presencial',
                'Plataformas Digitais' => 'plataformas-digitais'
            ];

            foreach ($metricasExibir as $origem => $id):
                $valorMetrica = isset($metricas[$origem]) ? $metricas[$origem] : 0;
        ?>
            <div class="metrica" id="<?php echo $id; ?>">
                <h3><?php echo htmlspecialchars($origem); ?></h3>
                <p><?php echo htmlspecialchars($valorMetrica); ?></p>
            </div>
        <?php endforeach; ?>
    </section>












    <section class="tempo-medio">
        <div class="card">
            <h3>Total de Certidões no Mês</h3>
            <p><?php echo $total_certidoes; ?></p>
        </div>
        <div class="card">
            <h3>Tempo Médio Total</h3>
            <p>
                <?php
                $dias = floor($tempo_medio_total / 24);
                $horas_restantes = $tempo_medio_total - ($dias * 24);
                $horas = floor($horas_restantes);
                $minutos = round(($horas_restantes - $horas) * 60);
                echo $dias . " dia" . ($dias != 1 ? "s" : "") . " " . $horas . " hora" . ($horas != 1 ? "s" : "") . " e " . $minutos . " minuto" . ($minutos != 1 ? "s" : "");
                ?>
            </p>
        </div>

        <section class="metricas-extra">
            
            <div class="card">
                <h3>Tempo Médio Cliente na Recepção</h3>
                <p>
                    <?php
                    $dias = floor($tempo_medio_recepcao / 24);
                    $horas_restantes = $tempo_medio_recepcao - ($dias * 24);
                    $horas = floor($horas_restantes);
                    $minutos = round(($horas_restantes - $horas) * 60);
                    echo $dias . " dia" . ($dias != 1 ? "s" : "") . " " . $horas . " hora" . ($horas != 1 ? "s" : "") . " e " . $minutos . " minuto" . ($minutos != 1 ? "s" : "");
                    ?>
                </p>
            </div>
        </section>
        <div class="card">
            <h3>Tempo Médio por Tipo de Certidão</h3>
            <?php if (!empty($tempo_medio_por_tipo)): ?>
                <ul>
                    <?php foreach ($tempo_medio_por_tipo as $tipo => $tempo): ?>
                        <li>
                            <?php
                            $dias = floor($tempo / 24);
                            $horas_restantes = $tempo - ($dias * 24);
                            $horas = floor($horas_restantes);
                            $minutos = round(($horas_restantes - $horas) * 60);
                            echo htmlspecialchars($tipo) . ": " . $dias . " dia" . ($dias != 1 ? "s" : "") . " " . $horas . "h " . $minutos . "min";
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p>Nenhum dado disponível para esta métrica.</p>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>
