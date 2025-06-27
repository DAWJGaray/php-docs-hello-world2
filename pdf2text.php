<?php
require __DIR__ . '/vendor/autoload.php';

use MicrosoftAzure\Storage\Blob\BlobRestProxy;
use MicrosoftAzure\Storage\Blob\Models\ListBlobsOptions;
use Smalot\PdfParser\Parser;

//Sacar errores
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

// Configuración
$connectionString = getenv("AZURE_STORAGE_CONNECTION_STRING");
$containerNamePdf = "pdfcontainercm";  // Nombre de tu contenedor de PDFs
$containerNameText = "textcontainercm"; // Nombre del contenedor donde guardarás los textos

// Crear el cliente de Azure Blob
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

// Listar los blobs (archivos PDF) en el contenedor
$listOptions = new ListBlobsOptions();
$blobList = $blobClient->listBlobs($containerNamePdf, $listOptions);
$blobs = $blobList->getBlobs();

<!DOCTYPE html>
<html>
<head>
    <title>Gestor de archivos ZIP en Azure Blob</title>
</head>
<body>
    <h1>Archivos en el contenedor '<?= htmlspecialchars(
        $containerNamePdf
    ) ?>'</h1>
    <ul>
    
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

    
    //echo "Fichero $blobName extraído y guardado en: $textBlobName\n";
    //https://almacenclasecm.blob.core.windows.net/pdfcontainercm/U3IntroSO.pdf
        <li><p>Fichero </p>                
                <a href="<?= htmlspecialchars(
                    $blob->getUrl()
                ) ?>" target="_blank">
                    <?= htmlspecialchars($blob->getName()) ?>
                </a>
            <p> extraído y guardado en <b>echo "$textBlobName"</b></p> 
        </li>
    </ul>
    <h2>Subir nuevo archivo ZIP</h2>
</body>
</html>
        
?>
