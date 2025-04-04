<?php
// Específica qual URL pode acessar
header('Access-Control-Allow-Origin: http://localhost:8080');

// Especifica qual método http é aceito
header('Access-Control-Allow-Methods: GET');

// Cabeçalhos que podem ser recebidos
header('Access-Control-Allow-Headers: Content-Type');

// Tipo de conteúdo que é aceito no back-end
header("Content-Type: application/json");

// Requerindo o arquivo que faz a conexão com o banco de dados
require_once '../../../database/conn.php';

// Função para retornar todos os produtos
function retornaProdutos($nif, $pagina, $conn)
{

    $quantidadeDeProdutos = 5;

    $inicioProdutos = $pagina * $quantidadeDeProdutos;

    // Preparando a query
    $stmt = $conn->prepare("SELECT * FROM vw_produtos 
    WHERE NIF = ? AND (Status = 'Aceito' OR Status = 'Em Análise') LIMIT ?, ?");;
    $stmt->bind_param("sii", $nif, $inicioProdutos, $quantidadeDeProdutos); // "i" indica um valor inteiro

    $stmt->execute();
    $resultado = $stmt->get_result();

    // Array para pegar todos os produtos retornados
    $produtos = array();

    if ($resultado->num_rows > 0) {

        while ($linha = $resultado->fetch_assoc()) {
            // Processar os resultados
            $produtos[] = $linha;
        }

        // Caso a quantidade de botoes já tenha sido calculada anteriormente
        // ele evitará de fazer uma busca ao banco desnecessária
        if ($_GET['qtdBotoes'] == -1) {
            $qtdBotoes = qtdBotoes($conn, $quantidadeDeProdutos, $nif);
        } else {
            $qtdBotoes = $_GET['qtdBotoes'];
        }

        // Enviando a resposta do servidor
        $resposta = [
            'status' => 'success',
            'mensagem' => 'Produtos retornados com sucesso',
            'produtos' => $produtos,
            'qtdBotoes' => $qtdBotoes
        ];

    } else {

        $resposta = [
            'status' => 'success',
            'mensagem' => 'Nenhum produto encontrado'
        ];

    }


    echo json_encode($resposta);

}

// Retorna a quantidade de funcionários
function qtdBotoes($conn, $quantidadeDeProdutosTela, $nif) {
    // preparando a query
    $stmt = $conn->prepare("SELECT COUNT(idProduto) FROM Produtos WHERE fk_nifTecnico = ? ");

    $stmt->bind_param('s', $nif);

    // Excutando a query
    $stmt->execute();

    $resultado = $stmt->get_result();

    // Retornando a quantidade de funcionarios
    $qtdProdutos = intval($resultado->fetch_all()[0][0]);

    // Calculando a quantidade de botoes, dividindo a quantidade de funcionarios no banco 
    // pela quantidade de funcionario por tela
    return ceil($qtdProdutos / $quantidadeDeProdutosTela);
}

// Verificando a requisição
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Pegando o nif para realizar a query e puxar todos os produtos que são vinculados com o técnico em especifico
    $nif = $_GET['nif'];

    // Pegando a numerção da página
    $pagina = $_GET['pagina'];

    retornaProdutos($nif, $pagina, $conn);

} else {

    // Envindo a resposta para o front-end
    $resposta = [
        'status' => 'error',
        'Mensagem' => 'Ocorreu algum erro...'
    ];

    echo json_encode($resposta);
}
?>