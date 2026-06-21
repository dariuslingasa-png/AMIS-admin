<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Repositories\TeacherRepository;

$sourceDir = '/home/tatsuya/Downloads/FACULTY POSTER/';
$targetDir = __DIR__ . '/../public/images/teachers/';

// Create target directory if it doesn't exist
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$mapping = [
    'Kinder1.png' => [
        'id' => 'teacher-wendelyn-bangot', // Wait! Let's make sure the ID matches what's in overrides. Let's inspect the ID in overrides for Wendelyn.
        'target' => 'wendelyn-bangot.png',
    ],
    'Kinder2_Girls.png' => [
        'id' => 'teacher-ayah-baguinsodon',
        'target' => 'ayah-baguinsodon.png',
    ],
    'Kinder2_Boys.png' => [
        'id' => 'teacher-joanna-lafuente',
        'target' => 'joanna-lafuente.png',
    ],
    'G1.png' => [
        'id' => 'teacher-sahdia-landas',
        'target' => 'sahdia-landas.png',
    ],
    'G2.png' => [
        'id' => 'teacher-marham-dalano-lupon',
        'target' => 'marham-lupon.png',
    ],
    'G3.png' => [
        'id' => 'teacher-jerlyn-mijares',
        'target' => 'teacher-jerlyn-mijares.png',
    ],
    'G4.png' => [
        'id' => 'teacher-monisa-gegare-balandan',
        'target' => 'monisa-gegare-balandan.png',
    ],
    'G5.png' => [
        'id' => 'teacher-jessa-mae-recla',
        'target' => 'jessa-mae-recla.png',
    ],
    'G6.png' => [
        'id' => 'teacher-normylah-bangon',
        'target' => 'normylah-bangon.png',
    ],
    'G7.png' => [
        'id' => 'teacher-sophia-macarimbang',
        'target' => 'sophia-macarimbang.png',
    ],
    'G8.png' => [
        'id' => 'teacher-shirehan-lais',
        'target' => 'shirehan-lais.png',
    ],
    'G9.png' => [
        'id' => 'teacher-norhaima-bautista',
        'target' => 'norhaima-bautista.png',
    ],
    'G10.png' => [
        'id' => 'teacher-nadzra-ainin',
        'target' => 'nadzra-ainin.png',
    ],
    'G11.png' => [
        'id' => 'teacher-jhelyn-nina-montes',
        'target' => 'jhelyn-montes.png',
    ],
    'G12.png' => [
        'id' => 'teacher-ethel-lorraine',
        'target' => 'ethel-lorraine.png',
    ],
    'TeachKat.png' => [
        'id' => 'tchr-katrina',
        'target' => 'tchr-katrina.png',
    ],
    'TeachArvin.png' => [
        'id' => 'tchr-arvin',
        'target' => 'tchr-arvin.png',
    ],
    'TeachAnna.png' => [
        'id' => 'teacher-anna',
        'target' => 'teacher-anna.png',
    ],
    'TeachNof.png' => [
        'id' => 'teacher-nof',
        'target' => 'teacher-nof.png',
    ],
    'TeachWeng.png' => [
        'id' => 'teacher-weng',
        'target' => 'teacher-weng.png',
    ],
    'TeachHalnaisa.png' => [
        'id' => 'teacher-halnaisa',
        'target' => 'teacher-halnaisa.png',
    ],
    'TeachZarah.png' => [
        'id' => 'teacher-zarah',
        'target' => 'teacher-zarah.png',
    ],
    'TeachAngeleni.png' => [
        'id' => 'teacher-angeleni',
        'target' => 'teacher-angeleni.png',
    ],
    'TeachAnah.png' => [
        'id' => 'teacher-aniah',
        'target' => 'teacher-aniah.png',
    ],
    'TeachHannah.png' => [
        'id' => 'teacher-hannah',
        'target' => 'teacher-hannah.png',
    ],
    'TeachWardah.png' => [
        'id' => 'teacher-wardah',
        'target' => 'teacher-wardah.png',
    ],
    'TeachRadzma.png' => [
        'id' => 'teacher-radzmia',
        'target' => 'teacher-radzmia.png',
    ],
    'TeachJairah.png' => [
        'id' => 'teacher-jairah',
        'target' => 'teacher-jairah.png',
    ],
    'UstadhaSilfah.png' => [
        'id' => 'ust-silfah',
        'target' => 'ust-silfah.png',
    ],
    'UstadhaRaslina.png' => [
        'id' => 'ustadha-raslina',
        'target' => 'ustadha-raslina.png',
    ],
    'UstadhaSaliha.png' => [
        'id' => 'ustadha-saliha',
        'target' => 'ustadha-saliha.png',
    ],
    'UstadzAli.png' => [
        'id' => 'ustadz-ali',
        'target' => 'ustadz-ali.png',
    ],
    'UstadzObaydah.png' => [
        'id' => 'ustadz-obaydah',
        'target' => 'ustadz-obaydah.png',
    ],
    'AlimAbdulkarim.png' => [
        'id' => 'alim-abdulkarim',
        'target' => 'alim-abdulkarim.png',
    ],
    'AlimSamsuddin.png' => [
        'id' => 'alim-samsuddin',
        'target' => 'alim-samsuddin.png',
    ],
    'UstadzJaisam.png' => [
        'id' => 'ustadh-jaisam',
        'target' => 'ustadh-jaisam.png',
    ],
    'UstadzAbdi.png' => [
        'id' => 'ust-abdiraheem',
        'target' => 'ust-abdiraheem.png',
    ],
    'UstadzErsahad.png' => [
        'id' => 'ust-ersahad',
        'target' => 'ust-ersahad.png',
    ],
    'AlimAhmad.png' => [
        'id' => 'alim-ahmad',
        'target' => 'alim-ahmad.png',
    ],
    'AlimAbdulwahab.png' => [
        'id' => 'alim-abdulwahab',
        'target' => 'alim-abdulwahab.png',
    ],
];

// Special handling for the Samsuddin file with backslash in list_dir output
if (file_exists($sourceDir . 'AlimSamsuddin\\.png')) {
    rename($sourceDir . 'AlimSamsuddin\\.png', $sourceDir . 'AlimSamsuddin.png');
}

$repo = app(TeacherRepository::class);
$overrides = $repo->overrides();

foreach ($mapping as $sourceFile => $info) {
    $fullSourcePath = $sourceDir . $sourceFile;
    if (!file_exists($fullSourcePath)) {
        // Try fallback without escaping if needed
        $cleanSource = str_replace('\\', '', $sourceFile);
        $fullSourcePath = $sourceDir . $cleanSource;
    }
    
    if (file_exists($fullSourcePath)) {
        $targetFile = $info['target'];
        $fullTargetPath = $targetDir . $targetFile;
        
        // Copy the file
        copy($fullSourcePath, $fullTargetPath);
        echo "Copied {$sourceFile} -> {$targetFile}\n";
        
        // Update overrides JSON
        $id = $info['id'];
        if (isset($overrides[$id])) {
            $overrides[$id]['photo'] = 'images/teachers/' . $targetFile;
            echo "  Updated photo path for override ID: {$id}\n";
        }
    } else {
        echo "Warning: Source file not found: {$fullSourcePath}\n";
    }
}

// Save JSON overrides
$repo->saveOverrides($overrides);
echo "\nDONE! Sync and configuration update finished.\n";
