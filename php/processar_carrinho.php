<?php
require("connection-sql.php");
session_start();
$usuario_id = $_SESSION['id'];

// Verifica se os dados do carrinho foram recebidos via POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Recebe os dados do carrinho como JSON e decodifica-os
    $dados_carrinho = json_decode(file_get_contents("php://input"), true);

    // Verifica se o número do pedido está presente nos dados do carrinho
    if (isset($dados_carrinho[0]['numPedido'])) {
        $numPedido = $dados_carrinho[0]['numPedido']; // Usa o número do pedido enviado pelo JavaScript
    } else {
        http_response_code(400); // Bad Request
        echo "Erro: Número do pedido não encontrado nos dados do carrinho.";
        exit();
    }

    // Verifica se o usuário está logado e se o ID do usuário está definido na sessão
    if (!isset($_SESSION['email'])) {
        // Se o usuário não estiver logado ou o ID do usuário não estiver definido na sessão,
        // redirecionar para a página de login
        header('Location: welcome.html');
        exit();
    } else {
        // Se o usuário estiver logado, exibir os dados do usuário
        if(isset($_SESSION['id'])) {
            $usuario_id = $_SESSION['id'];
        } else {
            // Se o ID do usuário não estiver definido na sessão, exibir uma mensagem de erro e sair do script
            echo "Erro: O ID do usuário não está definido na sessão.";
            exit();
        }
    }

    // Verifica se os dados do carrinho são válidos
    if (!is_array($dados_carrinho)) {
        http_response_code(400); // Bad Request
        echo "Erro: Os dados do carrinho estão vazios ou em um formato inválido.";
        exit();
    }

    // Calcula o valor total do pedido de acordo com o carrinho
    $total_pedido = 0;
    foreach ($dados_carrinho as $item) {
        if (isset($item['preco']) && isset($item['quantidade'])) {
            $total_pedido += $item['preco'] * $item['quantidade'];
        } else {
            http_response_code(400); // Bad Request
            echo "Erro: Os dados do carrinho estão incompletos.";
            exit();
        }
    }

    // Prepara e executa a consulta SQL para inserir os dados do pedido na tabela `pedido`
    $n = "Não";
    $stmt = $conn->prepare("INSERT INTO pedido (num_pedido, valor_pedido, pago) VALUES (?, ?, ?)");
    $stmt->bind_param("ids", $numPedido, $total_pedido, $n);
    $stmt->execute();
    $stmt->close();

    // Prepara e executa a consulta SQL para inserir os dados do carrinho na tabela `pedidoproduto`
    $stmt = $conn->prepare("INSERT INTO pedidoproduto (num_pedido, usuario_id, nome_produto, quantidade, valor_unitario) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisid", $numPedido, $usuario_id, $nome, $quantidade, $preco);

    foreach ($dados_carrinho as $item) {
        // Atribui os valores às variáveis a partir do item
        $nome = $item['nome'];
        $preco = $item['preco'];
        $quantidade = $item['quantidade'];

        // Executa a consulta preparada para cada item
        $stmt->execute();
    }

    // Fecha a consulta preparada
    $stmt->close();

    // Responde ao JavaScript com uma mensagem de sucesso
    echo "Dados do carrinho recebidos com sucesso.";
} else {
    // Se os dados do carrinho estiverem vazios, retorna uma mensagem de erro
    http_response_code(400); // Bad Request
    echo "Erro: Os dados do carrinho estão vazios ou em um formato inválido.";
    exit();
}
