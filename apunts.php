<?php
require 'vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Common\Exceptions\ServiceException;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use Smalot\PdfParser\Parser;

//Sacar errores
ini_set('display_errors', 1);
error_reporting(E_ALL);

$connectionString = getenv("AZURE_STORAGE_CONNECTION_STRING");
//"DefaultEndpointsProtocol=https;AccountName=YOUR_ACCOUNT_NAME;AccountKey=YOUR_ACCOUNT_KEY;EndpointSuffix=core.windows.net";
$containerNamePdf = "pdfcontainercm";  // Nombre de tu contenedor de PDFs
$containerNameText = "textcontainercm"; // Nombre del contenedor de los textos
$containerNameTranslated = "translatedcontainercm"; //Nombre PDFs traducidos

$blobClient = BlobRestProxy::createBlobService($connectionString);

// Función para leer PDF desde Azure Blob Storage
function readPdfFromBlob($blobName, $containerName) {
    global $blobClient;
    
    $blob = $blobClient->getBlob($containerName, $blobName);
    $content = stream_get_contents($blob->getContentStream());

    return $content;
}

// Función para extraer texto del PDF
function extractTextFromPdf($pdfContent) {
    $parser = new Parser();
    $pdf = $parser->parseContent($pdfContent);
    $text = $pdf->getText();
    
    return $text;
}

// Función para guardar texto plano en el Blob Storage
function saveTextToBlob($text, $containerName, $blobName) {
    global $blobClient;
    
    $blobClient->createBlockBlob($containerName, $blobName, $text);
}


try {
   // Listar los blobs (archivos PDF) en el contenedor
    $listOptions = new ListBlobsOptions();
    $blobList = $blobClient->listBlobs($containerNamePdf, $listOptions);
    $blobs = $blobList->getBlobs();
} catch (ServiceException $e) {
    die("Error al conectar con Azure Storage: " . $e->getMessage());
}

// Iterar sobre los blobs (PDFs) y extraer el texto
foreach ($blobs as $blob) {
    $blobName = $blob->getName();
    
    // Leer el contenido del PDF desde Blob Storage
    $pdfContent = readPdfFromBlob($blobName, $containerNamePdf);
    
    // Extraer el texto del PDF
    $text = extractTextFromPdf($pdfContent);
    
    // Generar un nuevo nombre para el archivo de texto
    $textBlobName = pathinfo($blobName, PATHINFO_FILENAME) . '.txt';
    
    // Guardar el texto extraído en el contenedor de texto
    saveTextToBlob($text, $containerNameText, $textBlobName);
    
    echo "Fichero $blobName extraído y guardado en: $textBlobName\n";
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Archivos en Azure Blob Storage</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            background: white;
            margin: auto;
            padding: 20px;
            border-radius: 8px;
        }
        h1 {
            text-align: center;
        }
        ul {
            list-style: none;
            padding-left: 0;
        }
        li {
            margin: 10px 0;
            background: #e8f0fe;
            padding: 10px;
            border-radius: 5px;
        }
        a {
            text-decoration: none;
            color: #1a73e8;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contenidos del contenedor: <?= htmlspecialchars($containerNamePdf) ?></h1>
        <ul>
            <?php foreach ($blobs as $blob): ?>
                <li>
                    <a href="https://almacenclasecm.blob.core.windows.net/<?= $containerNamePdf ?>/<?= urlencode($blob->getName()) ?>" target="_blank">
                        <?= htmlspecialchars($blob->getName()) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <div class="container">
        <h1>Contenidos del contenedor: <?= htmlspecialchars($containerNameText) ?></h1>
        <ul>
            <?php foreach ($blobs as $blob): ?>
                <li>
                    <a href="https://almacenclasecm.blob.core.windows.net/<?= $containerNameText ?>/<?= urlencode($textBlobName) ?>" target="_blank">
                        <?= htmlspecialchars($textBlobName) ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html>
