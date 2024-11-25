<?php
include_once('./componentes/metricas/bd.php');

// Função para formatar datas do banco para o formato dd/mm/yyyy hh:mm
function formatarData($data) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $data);
    if ($dt !== false) {
        return $dt->format('d/m/Y H:i');
    }
    return '';
}

// Função para formatar datas para o formato input datetime-local
function formatarDataInput($data) {
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $data);
    if ($dt !== false) {
        return $dt->format('Y-m-d\TH:i');
    }
    return '';
}

// Função para obter métricas de certidões por origem
function obterMetricas($conn, $mes = null) {
    $query = "SELECT origem, COUNT(*) as total FROM certidao";
    $params = [];
    $types = "";

    if ($mes) {
        $query .= " WHERE mes = ?";
        $params[] = $mes;
        $types .= "i"; // Supondo que 'mes' seja um inteiro
    }

    $query .= " GROUP BY origem";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . htmlspecialchars($conn->error));
    }

    if ($mes) {
        // Passar parâmetros por referência
        foreach ($params as &$param) {
            // Nada a fazer aqui, só precisamos que seja uma referência
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }

    if (!$stmt->execute()) {
        die("Erro na execução da consulta: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result === false) {
        die("Erro ao obter resultado: " . htmlspecialchars($stmt->error));
    }

    // Inicializar as métricas com os valores corretos
    $metricas = [
        'Total de Pedidos' => 0,
        'ONR' => 0,
        'Cartórios Maranhão' => 0,
        'Presencial' => 0,
        'Plataformas Digitais' => 0
    ];

    while ($row = $result->fetch_assoc()) {
        $origem = $row['origem'];
        $total = (int)$row['total']; // Converte para inteiro para evitar problemas de tipo
        $metricas['Total de Pedidos'] += $total;

        if ($origem === 'ONR') {
            $metricas['ONR'] += $total;
        } elseif ($origem === 'Cartórios Maranhão') {
            $metricas['Cartórios Maranhão'] += $total;
        } elseif ($origem === 'Cliente na recepção') {
            $metricas['Presencial'] += $total;
        } elseif ($origem === 'Presencial') {
            $metricas['Presencial'] += $total;
        }
    }

    // Calcular "Plataformas Digitais" como a soma de "ONR" e "Cartórios Maranhão"
    $metricas['Plataformas Digitais'] = $metricas['ONR'] + $metricas['Cartórios Maranhão'];

    $stmt->close();

    return $metricas;
}


// Função para calcular o tempo médio total de todas as certidões (usando a coluna intervalo)
function calcularTempoMedioTotal($conn, $mes = null) {
    $query = "SELECT AVG(intervalo) AS tempo_medio_total FROM certidao";
    $params = [];
    $types = "";

    if ($mes) {
        $query .= " WHERE mes = ?";
        $params[] = $mes;
        $types .= "i"; // Supondo que 'mes' seja um inteiro
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . htmlspecialchars($conn->error));
    }

    if ($mes) {
        // Passar parâmetros por referência
        foreach ($params as &$param) {
            // Nada a fazer aqui, só precisamos que seja uma referência
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }

    if (!$stmt->execute()) {
        die("Erro na execução da consulta: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result === false) {
        die("Erro ao obter resultado: " . htmlspecialchars($stmt->error));
    }

    $tempo_medio_total = 0;

    if ($row = $result->fetch_assoc()) {
        $tempo_medio_total = $row['tempo_medio_total'] !== null ? round($row['tempo_medio_total'], 2) : 0; // Verifica se não é nulo antes de arredondar
    }

    $stmt->close();

    return $tempo_medio_total;
}

// Função para calcular o tempo médio por tipo de certidão (usando a coluna intervalo)
function calcularTempoMedioPorTipoCertidao($conn, $mes = null) {
    $query = "SELECT tipo, AVG(intervalo) AS tempo_medio FROM certidao";
    $params = [];
    $types = "";

    if ($mes) {
        $query .= " WHERE mes = ?";
        $params[] = $mes;
        $types .= "i"; // Supondo que 'mes' seja um inteiro
    }

    $query .= " GROUP BY tipo";

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . htmlspecialchars($conn->error));
    }

    if ($mes) {
        // Passar parâmetros por referência
        foreach ($params as &$param) {
            // Nada a fazer aqui, só precisamos que seja uma referência
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }

    if (!$stmt->execute()) {
        die("Erro na execução da consulta: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result === false) {
        die("Erro ao obter resultado: " . htmlspecialchars($stmt->error));
    }

    $tempo_medio_por_tipo = [];

    while ($row = $result->fetch_assoc()) {
        $tempo_medio_por_tipo[$row['tipo']] = round($row['tempo_medio'], 2);
    }

    $stmt->close();

    return $tempo_medio_por_tipo;
}

// Função para obter certidões por origem
function obterCertidoesPorOrigem($conn, $origem, $mes = null) {
    $query = "SELECT * FROM certidao WHERE origem = ?";
    $params = [$origem];
    $types = "s";

    if ($mes) {
        $query .= " AND mes = ?";
        $params[] = $mes;
        $types .= "i"; // Supondo que 'mes' seja um inteiro
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . htmlspecialchars($conn->error));
    }

    // Passar parâmetros por referência
    foreach ($params as &$param) {
        // Nada a fazer aqui, só precisamos que seja uma referência
    }

    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));

    if (!$stmt->execute()) {
        die("Erro na execução da consulta: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result === false) {
        die("Erro ao obter resultado: " . htmlspecialchars($stmt->error));
    }

    $certidoes = [];

    while ($row = $result->fetch_assoc()) {
        $certidoes[] = $row;
    }

    $stmt->close();

    return $certidoes;
}

// Função para obter uma certidão por ID
function obterCertidaoPorId($conn, $id) {
    $query = "SELECT * FROM certidao WHERE num_certidao = ?";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        die("Erro na preparação da consulta: " . htmlspecialchars($conn->error));
    }

    $stmt->bind_param("i", $id); // Passando o parâmetro diretamente, já que é apenas um
    $stmt->execute();
    $result = $stmt->get_result();
    $certidao = $result->fetch_assoc();

    $stmt->close();

    return $certidao;
}

// Função para obter o total de certidões no mês
function calcularTotalCertidoesNoMes($conn, $mes = null) {
    $query = "SELECT SUM(quantidade) AS total_certidoes FROM certidao";
    $params = [];
    $types = "";

    if ($mes) {
        $query .= " WHERE mes = ?";
        $params[] = $mes;
        $types .= "i"; // Supondo que 'mes' seja um inteiro
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . htmlspecialchars($conn->error));
    }

    if ($mes) {
        // Passar parâmetros por referência
        foreach ($params as &$param) {
            // Nada a fazer aqui, só precisamos que seja uma referência
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }

    if (!$stmt->execute()) {
        die("Erro na execução da consulta: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result === false) {
        die("Erro ao obter resultado: " . htmlspecialchars($stmt->error));
    }

    $total_certidoes = 0;

    if ($row = $result->fetch_assoc()) {
        $total_certidoes = $row['total_certidoes'] !== null ? (int)$row['total_certidoes'] : 0;
    }

    $stmt->close();

    return $total_certidoes;
}

// Função para obter a média de certidões por mês
function calcularMediaCertidoesNoMes($conn, $mes = null) {
    $query = "SELECT AVG(quantidade) AS media_certidoes FROM certidao";
    $params = [];
    $types = "";

    if ($mes) {
        $query .= " WHERE mes = ?";
        $params[] = $mes;
        $types .= "i"; // Supondo que 'mes' seja um inteiro
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . htmlspecialchars($conn->error));
    }

    if ($mes) {
        // Passar parâmetros por referência
        foreach ($params as &$param) {
            // Nada a fazer aqui, só precisamos que seja uma referência
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }

    if (!$stmt->execute()) {
        die("Erro na execução da consulta: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result === false) {
        die("Erro ao obter resultado: " . htmlspecialchars($stmt->error));
    }

    $media_certidoes = 0;

    if ($row = $result->fetch_assoc()) {
        $media_certidoes = $row['media_certidoes'] !== null ? round($row['media_certidoes'], 2) : 0;
    }

    $stmt->close();

    return $media_certidoes;
}

function calcularTempoMedioClienteRecepcao($conn, $mes = null) {
    $query = "SELECT AVG(intervalo) AS tempo_medio_recepcao FROM certidao WHERE origem = 'Cliente na recepção'";
    $params = [];
    $types = "";

    if ($mes) {
        $query .= " AND mes = ?";
        $params[] = $mes;
        $types .= "i"; // Supondo que 'mes' seja um inteiro
    }

    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Erro na preparação da consulta: " . htmlspecialchars($conn->error));
    }

    if ($mes) {
        // Passar parâmetros por referência
        foreach ($params as &$param) {
            // Nada a fazer aqui, só precisamos que seja uma referência
        }
        call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    }

    if (!$stmt->execute()) {
        die("Erro na execução da consulta: " . htmlspecialchars($stmt->error));
    }

    $result = $stmt->get_result();
    if ($result === false) {
        die("Erro ao obter resultado: " . htmlspecialchars($stmt->error));
    }

    $tempo_medio_recepcao = 0;

    if ($row = $result->fetch_assoc()) {
        $tempo_medio_recepcao = $row['tempo_medio_recepcao'] !== null ? round($row['tempo_medio_recepcao'], 2) : 0;
    }

    $stmt->close();

    return $tempo_medio_recepcao;
}

?>
