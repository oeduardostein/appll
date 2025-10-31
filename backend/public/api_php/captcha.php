<?php 
header('Content-Type: image/jpeg');

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

$placa = $_GET['placa'];
$captchaResponse = $_GET['captcha'];
$renavam = $_GET['renavam'];
// Configurações de conexão com o banco de dados
$servername = '100.27.58.138';
$username = 'youdesp';
$password = 'Skala@2024$';
$dbname = 'youdesp';

// Criar conexão
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}

// Buscar o token atual
$sql = "SELECT token FROM tokens WHERE id = 1";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $token_atual = $row["token"];
} else {
    $token_atual = "Nenhum token encontrado";
}

// Fechar conexão
$conn->close();

// URL do captcha e da requisição
$captcha_url = 'https://www.e-crvsp.sp.gov.br/gever/jcaptcha?id='.time();
$request_url = 'https://www.e-crvsp.sp.gov.br/gever/GVR/pesquisa/baseEstadual.do';

// Inicializa o cURL para capturar o captcha
$ch = curl_init($captcha_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3;q=0.7',
    'Accept-Language: pt-BR,pt;q=0.9,en-US;q=0.8,en;q=0.7',
    'Cache-Control: max-age=0',
    'Connection: keep-alive',
    "Cookie: dataUsuarPublic=Thu%20Aug%2001%202024%2014%3A46%3A49%20GMT-0300%20(Hor%C3%A1rio%20Padr%C3%A3o%20de%20Bras%C3%ADlia); naoExibirPublic=sim; JSESSIONID=$token_atual",
    'Sec-Fetch-Dest: document',
    'Sec-Fetch-Mode: navigate',
    'Sec-Fetch-Site: none',
    'Sec-Fetch-User: ?1',
    'Upgrade-Insecure-Requests: 1',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0.0.0 Safari/537.36',
    'sec-ch-ua: "Chromium";v="130", "Google Chrome";v="130", "Not?A_Brand";v="99"',
    'sec-ch-ua-mobile: ?0',
    'sec-ch-ua-platform: "Windows"'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$captcha_image_data = curl_exec($ch);
curl_close($ch);

// Salva a imagem do captcha
$captcha_image_path = 'captcha.jpg';
file_put_contents($captcha_image_path, $captcha_image_data);
 echo 'teste'; die();
echo file_get_contents($captcha_image_path);
