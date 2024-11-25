<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certidão</title>

    <link rel="stylesheet" href="./index.css">
    <link rel="shortcut icon" href="LOGO_3RI 1_layerstyle.ico" type="image/x-icon">
</head>
<body>
    <?php
    require('./componentes/navbar/navbar.php');

    // Verifica qual página deve ser carregada
    $pagina = isset($_GET['pagina']) ? $_GET['pagina'] : 'inicio';
?>

    <div class="conteudo">
        <?php
        // Inclui o arquivo com base no parâmetro da URL
            if ($pagina === 'metricas') {
                require('./componentes/metricas/metricas.php');
            } else {
                require('./componentes/principal/principal.php');
            }
            ?>
    </div>
    
</body>
</html>
