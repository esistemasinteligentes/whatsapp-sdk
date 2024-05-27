<?php

require 'vendor/autoload.php';
require 'src/WhatsappSdk/SDK.php';

// use WhatsappSdk\SDK;
try {
    echo '<pre>';


    // Defina suas credenciais de API
    $instanceKey = "ESI-Test";
    $globalToken = "zYzP7ocstxh3Sscefew4FZTCu4ehnM8v4hu0";

    // Crie uma nova instância da classe SDK
    $whatsappSdk = new SDK($instanceKey, $globalToken);

    // Obtenha o estado da sessão
    $sessionResponse = $whatsappSdk->getSession();
    print_r($sessionResponse);
    if (count($sessionResponse) <= 0) {
        // Inicie uma nova sessão
        $sessionResponse = $whatsappSdk->initSession();
        print_r($sessionResponse);

        $whatsappSdk->setToken($sessionResponse['Auth']['token']);

        $connectResponse = $whatsappSdk->getQRCodeBase64();
        print_r($connectResponse);
        echo '<img src="' . $connectResponse['base64'] . '" />';
    } else {
        $sessionResponse = $sessionResponse[0];
        $whatsappSdk->setToken($sessionResponse['Auth']['token']);
    }



    // // Envie uma mensagem de texto
    // $info = [
    //     'telefone' => '63999632031', // Número de telefone no formato internacional
    //     'message' => 'Olá, esta é uma mensagem de teste!'
    // ];
    // $messageResponse = $whatsappSdk->sendMessage($info);
    // print_r($messageResponse);

    // Envie um arquivo
    $fileInfo = [
        'telefone' => '63999632031',
        'link' => '/home/esi/Downloads/DASNSIMEI-Recibo-127508522023001 (1).pdf',
        'filename' => 'Orçamento.pdf',
        'description' => 'PEga teu arquivo.'
    ];
    $fileResponse = $whatsappSdk->sendMessageFilePDF($fileInfo);
    print_r($fileResponse);

    // // Obtenha o código QR para conectar
    // $qrCodeResponse = $whatsappSdk->getQRCodeBase64();
    // print_r($qrCodeResponse);

    // // Finalize a sessão
    // $deleteResponse = $whatsappSdk->deleteSession();
    // print_r($deleteResponse);
} catch (\Throwable $th) {
    //throw $th;
    echo $th->getMessage();
}
