<?php
require_once('./bd.php');

function isDiaUtil($data, $feriados) {
    $diaSemana = $data->format('N');
    if ($diaSemana >= 6) return false; // Sábado e domingo
    $dataFormatada = $data->format('d/m');
    return !in_array($dataFormatada, $feriados);
}

function calcularEInserirCertidao($num_certidao, $data_inicial, $data_final, $origem, $tipo, $quantidade) {
    global $conexao;
    $feriados = [
        '01/01', '21/04', '01/05', '07/09', '12/10', '02/11', '15/11', '20/11', '24/12', '25/12', '31/12'
    ];

    $inicio = DateTime::createFromFormat('d/m/Y H:i', $data_inicial);
    $fim = DateTime::createFromFormat('d/m/Y H:i', $data_final);

    if (!$inicio || !$fim) {
        echo "Erro: datas inválidas.";
        return false;
    }

    $segundosTotais = 0;
    $dataAtual = clone $inicio;

    while ($dataAtual <= $fim) {
        if (isDiaUtil($dataAtual, $feriados)) {
            $horaInicio = clone $dataAtual;
            $horaFim = clone $dataAtual;
            $horaInicio->setTime(8, 0);
            $horaFim->setTime(17, 0);

            if ($dataAtual->format('Y-m-d') == $inicio->format('Y-m-d') && $inicio > $horaInicio) {
                $horaInicio = clone $inicio;
            }

            if ($dataAtual->format('Y-m-d') == $fim->format('Y-m-d') && $fim < $horaFim) {
                $horaFim = clone $fim;
            }

            if ($horaInicio < $horaFim) {
                $segundosTotais += $horaFim->getTimestamp() - $horaInicio->getTimestamp();
            }
        }
        $dataAtual->modify('+1 day');
        $dataAtual->setTime(8, 0);
    }

    $intervalo = $segundosTotais / 3600;
    $mes = (int) $inicio->format('m');

    // Armazene as datas formatadas em variáveis antes de passá-las para bind_param
    $dataInicialFormatada = $inicio->format('Y-m-d H:i:s');
    $dataFinalFormatada = $fim->format('Y-m-d H:i:s');

    $sql = "INSERT INTO certidao (num_certidao, data_inicial, data_final, intervalo, origem, tipo, quantidade, mes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conexao, $sql);
    mysqli_stmt_bind_param($stmt, "ssssssii", $num_certidao, $dataInicialFormatada, $dataFinalFormatada, 
                            $intervalo, $origem, $tipo, $quantidade, $mes);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}


function atualizarCertidao($num_certidao, $data_inicial, $data_final, $origem, $tipo, $quantidade) {
    global $conexao;
    $feriados = [
        '01/01', '21/04', '01/05', '07/09', '12/10', '02/11', '15/11', '20/11', '25/12'
    ];

    $inicio = DateTime::createFromFormat('Y-m-d H:i:s', $data_inicial);
    $fim = DateTime::createFromFormat('Y-m-d H:i:s', $data_final);

    if (!$inicio || !$fim) {
        echo "<script>alert('Erro: Datas inválidas.');</script>";
        return false;
    }

    $segundosTotais = 0;
    $dataAtual = clone $inicio;

    while ($dataAtual <= $fim) {
        if (isDiaUtil($dataAtual, $feriados)) {
            $horaInicio = clone $dataAtual;
            $horaFim = clone $dataAtual;
            $horaInicio->setTime(8, 0);
            $horaFim->setTime(17, 0);

            // Ajusta o início e fim dentro do intervalo útil do dia
            if ($dataAtual->format('Y-m-d') == $inicio->format('Y-m-d')) {
                if ($inicio > $horaInicio) {
                    $horaInicio = $inicio;
                }
            }
            if ($dataAtual->format('Y-m-d') == $fim->format('Y-m-d')) {
                if ($fim < $horaFim) {
                    $horaFim = $fim;
                }
            }

            // Verifica se o intervalo do dia é válido
            if ($horaInicio < $horaFim) {
                $segundosTotais += $horaFim->getTimestamp() - $horaInicio->getTimestamp();
            }
        }
        $dataAtual->modify('+1 day');
        $dataAtual->setTime(8, 0);
    }

    $intervalo = $segundosTotais / 3600; // Converte segundos para horas
    $mes = (int) $inicio->format('m');

    $sql = "UPDATE certidao 
            SET data_inicial = ?, data_final = ?, intervalo = ?, origem = ?, tipo = ?, quantidade = ?, mes = ?
            WHERE num_certidao = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssssssss", $data_inicial, $data_final, $intervalo, $origem, $tipo, $quantidade, $mes, $num_certidao);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_errno($stmt)) {
            echo "<script>console.log('Erro na execução da consulta: " . mysqli_stmt_error($stmt) . "');</script>";
        }

        $result = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        return $result;
    } else {
        echo "<script>console.log('Erro ao preparar a consulta: " . mysqli_error($conexao) . "');</script>";
        return false;
    }
}


function obterCertidoesArray($mesAtual, $numCertidao = null) {
    global $conexao;
    
    // Inicia a consulta SQL
    $sql = "SELECT num_certidao, data_inicial, data_final, intervalo, tipo, origem 
            FROM certidao 
            WHERE mes = ?";
    
    // Se numCertidao for fornecido, adiciona o filtro
    if ($numCertidao) {
        $sql .= " AND num_certidao = ?";
    }
    
    // Ordena pelo número da certidão de forma decrescente
    $sql .= " ORDER BY num_certidao DESC";
    
    // Prepara a consulta SQL
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        die('Erro na preparação da consulta: ' . mysqli_error($conexao));
    }
    
    // Faz o bind dos parâmetros de acordo com os filtros passados
    if ($numCertidao) {
        mysqli_stmt_bind_param($stmt, "ii", $mesAtual, $numCertidao); // Se numCertidao for passado, faz o bind para "mes" e "numCertidao"
    } else {
        mysqli_stmt_bind_param($stmt, "i", $mesAtual); // Se não, faz o bind apenas para "mes"
    }
    
    // Executa a consulta SQL
    $executado = mysqli_stmt_execute($stmt);
    if (!$executado) {
        die('Erro ao executar a consulta: ' . mysqli_error($conexao));
    }

    // Obtém o resultado da consulta
    $result = mysqli_stmt_get_result($stmt);
    $certidoes = [];
    
    // Se houver resultados, armazena-os no array
    while ($row = mysqli_fetch_assoc($result)) {
        $certidoes[] = [
            'num' => $row['num_certidao'],
            'data_inicial' => $row['data_inicial'],
            'data_final' => $row['data_final'],
            'intervalo' => $row['intervalo'],
            'tipo' => $row['tipo'],
            'origem' => $row['origem']
        ];
    }
    // Fecha o statement
    mysqli_stmt_close($stmt);
    
    // Retorna os dados encontrados
    return $certidoes;
}


function deletarCertidao($num_certidao) {
    global $conexao; // Certifique-se de que a conexão com o banco de dados está disponível

    $sql = "DELETE FROM certidao WHERE num_certidao = ?";
    $stmt = mysqli_prepare($conexao, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $num_certidao);
        mysqli_stmt_execute($stmt);

        if (mysqli_stmt_errno($stmt)) {
            echo "<script>console.log('Erro na execução da consulta: " . mysqli_stmt_error($stmt) . "');</script>";
            return false;
        }

        $result = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

    } else {
        echo "<script>console.log('Erro ao preparar a consulta: " . mysqli_error($conexao) . "');</script>";
        return false;
    }
}

function filtrarCertidoes($mesAtual, $num_certidao = null, $limite = null, $offset = null) {
    global $conexao;

    // Base da consulta sem filtro de num_certidao
    $sql = "SELECT num_certidao, data_inicial, data_final, intervalo, tipo, origem 
            FROM certidao WHERE 1";  // WHERE 1 é um truque para sempre permitir adicionar condições adicionais

    // Se num_certidao for fornecido, adiciona a condição para num_certidao
    if ($num_certidao !== null) {
        $sql .= " AND num_certidao = ?";
    }

    // Se o mês for fornecido (e não estamos buscando por num_certidao específico), aplica o filtro de mês
    if ($num_certidao === null) {
        $sql .= " AND mes = ?";
    }

    // Ordena pela data de início (mais recente primeiro)
    $sql .= " ORDER BY num_certidao DESC";

    // Adicionar limite e offset para paginação, se fornecidos
    if ($limite !== null) {
        $sql .= " LIMIT ?";
    }
    if ($offset !== null) {
        $sql .= " OFFSET ?";
    }



    // Preparar a consulta
    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        die('Erro ao preparar a consulta: ' . mysqli_error($conexao));
    }

    // Definir os parâmetros para bind
    $params = [];
    $types = "";

    // Se houver filtro num_certidao, adiciona ao bind
    if ($num_certidao !== null) {
        $params[] = $num_certidao;
        $types .= "i";  // Tipo para num_certidao (inteiro)
    }

    // Se não estamos filtrando por num_certidao, usamos o mês
    if ($num_certidao === null) {
        $params[] = $mesAtual;
        $types .= "i";  // Tipo para mes (inteiro)
    }

    // Adicionar limite e offset, se fornecidos
    if ($limite !== null) {
        $params[] = $limite;
        $types .= "i";  // Tipo para limite (inteiro)
    }
    if ($offset !== null) {
        $params[] = $offset;
        $types .= "i";  // Tipo para offset (inteiro)
    }
    // Bind os parâmetros
    $bindParams = [];
    $bindParams[] = &$types; // Tipos
    foreach ($params as $key => $value) {
        $bindParams[] = &$params[$key]; // Cada parâmetro por referência
    }
    call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bindParams));

    // Executar a consulta
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Verificar se houve erro na execução da consulta
    if (!$result) {
        die('Erro na consulta: ' . mysqli_error($conexao));
    }

    // Retornar os resultados
    $certidoes = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $certidoes[] = [
            'num' => $row['num_certidao'],
            'data_inicial' => $row['data_inicial'],
            'data_final' => $row['data_final'],
            'intervalo' => $row['intervalo'],
            'tipo' => $row['tipo'],
            'origem' => $row['origem']
        ];
    }
    mysqli_stmt_close($stmt);


    return $certidoes;
}


function contarCertidoes($mesAtual, $num_certidao = null, $data_inicial = null, $data_final = null) {
    global $conexao;
    
    $sql = "SELECT COUNT(*) as total FROM certidao WHERE mes = ?";
    $params = [$mesAtual];
    $types = "i"; // O mês é um inteiro
    
    if (!empty($num_certidao)) {
        $sql .= " AND num_certidao = ?";
        $params[] = $num_certidao;
        $types .= "i";
    }
    
    if (!empty($data_inicial) && !empty($data_final)) {
        $sql .= " AND data_inicial BETWEEN ? AND ?";
        $params[] = $data_inicial;
        $params[] = $data_final;
        $types .= "ss";
    }

    $stmt = mysqli_prepare($conexao, $sql);
    if ($stmt === false) {
        die('Erro ao preparar a consulta: ' . mysqli_error($conexao));
    }

    if (!empty($params)) {
        $bindParams = [];
        $bindParams[] = &$types;
        foreach ($params as $key => $value) {
            $bindParams[] = &$params[$key];
        }
        call_user_func_array('mysqli_stmt_bind_param', array_merge([$stmt], $bindParams));
    }

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            return $row['total'];
        } else {
            echo "<script>console.log('Erro ao obter resultado: " . mysqli_error($conexao) . "');</script>";
            return 0;
        }
    } else {
        echo "<script>console.log('Erro ao executar a consulta: " . mysqli_stmt_error($stmt) . "');</script>";
        return 0;
    }
}
