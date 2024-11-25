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
        '01/01', '21/04', '01/05', '07/09', '12/10', '02/11', '15/11', '20/11', '25/12'
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

    // Prepare a consulta SQL
    $sql = "SELECT num_certidao, data_inicial, data_final, intervalo, tipo, origem FROM certidao WHERE mes = ?";

    if ($numCertidao) {
        $sql .= " AND num_certidao = ?";
    }

    // Ordenar pela coluna data_insercao em ordem decrescente
    $sql .= " ORDER BY num_certidao DESC"; 

    $stmt = mysqli_prepare($conexao, $sql);
    if (!$stmt) {
        die('Erro na preparação da consulta: ' . mysqli_error($conexao));
    }

    if ($numCertidao) {
        mysqli_stmt_bind_param($stmt, "ii", $mesAtual, $numCertidao);
    } else {
        mysqli_stmt_bind_param($stmt, "i", $mesAtual);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

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

function filtrarCertidoes($num_certidao = null, $data_inicial = null, $data_final = null, $limite = null, $offset = null) {
    global $conexao;
    $sql = "SELECT num_certidao, data_inicial, data_final, intervalo, tipo, origem, quantidade 
            FROM certidao WHERE 1 = 1";
    $params = [];
    $types = "";

    if (!empty($num_certidao)) {
        $sql .= " AND num_certidao = ?";
        $params[] = $num_certidao;
        $types .= "i";
    }

    if (!empty($data_inicial) && !empty($data_final)) {
        $sql .= " AND data_inicial BETWEEN ? AND ?";
        $params[] = $data_inicial;
        $params[] = $data_final;
        $types .= "ss"; // Dois parâmetros de string
    }

    // Adiciona limite e offset para paginação
    if ($limite !== null) {
        $sql .= " LIMIT ?";
        $params[] = $limite;
        $types .= "i"; // Parâmetro de inteiro
    }

    if ($offset !== null) {
        $sql .= " OFFSET ?";
        $params[] = $offset;
        $types .= "i"; // Parâmetro de inteiro
    }

    $stmt = mysqli_prepare($conexao, $sql);
    if ($stmt) {
        // Passando parâmetros por referência
        if (!empty($params)) {
            $references = [];
            $references[] = &$stmt; // primeiro parâmetro precisa ser a referência do stmt
            $references[] = $types;
            foreach ($params as $key => $value) {
                $references[] = &$params[$key]; // passa cada parâmetro por referência
            }
            call_user_func_array('mysqli_stmt_bind_param', $references);
        }

        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($result) {
                $certidoes = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $certidoes[] = $row;
                }
                mysqli_stmt_close($stmt);
                return $certidoes;
            } else {
                echo "<script>console.log('Erro ao obter resultado: " . mysqli_error($conexao) . "');</script>";
                return [];
            }
        } else {
            echo "<script>console.log('Erro ao executar a consulta: " . mysqli_stmt_error($stmt) . "');</script>";
            return [];
        }
    } else {
        echo "<script>console.log('Erro ao preparar a consulta: " . mysqli_error($conexao) . "');</script>";
        return [];
    }
}

function contarCertidoes($num_certidao = null, $data_inicial = null, $data_final = null) {
    global $conexao;
    $sql = "SELECT COUNT(*) as total FROM certidao WHERE 1 = 1";
    $params = [];
    $types = "";

    if (!empty($num_certidao)) {
        $sql .= " AND num_certidao = ?";
        $params[] = $num_certidao;
        $types .= "i";
    }

    if (!empty($data_inicial) && !empty($data_final)) {
        $sql .= " AND data_inicial BETWEEN ? AND ?";
        $params[] = $data_inicial;
        $params[] = $data_final;
        $types .= "ss"; // Dois parâmetros de string
    }

    $stmt = mysqli_prepare($conexao, $sql);
    if ($stmt) {
        // Passando parâmetros por referência
        if (!empty($params)) {
            $references = [];
            $references[] = &$stmt; // primeiro parâmetro precisa ser a referência do stmt
            $references[] = $types;
            foreach ($params as $key => $value) {
                $references[] = &$params[$key]; // passa cada parâmetro por referência
            }
            call_user_func_array('mysqli_stmt_bind_param', $references);
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
    } else {
        echo "<script>console.log('Erro ao preparar a consulta: " . mysqli_error($conexao) . "');</script>";
        return 0;
    }
}