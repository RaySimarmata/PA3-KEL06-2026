<?php

function testRepair($xml) {
    echo "Original: " . $xml . "\n";
    
    // 1. Gabungkan semua teks dalam satu paragraph
    $xml = preg_replace_callback(
        '/(<w:p[^>]*>)(.*?)(<\/w:p>)/s',
        function($matches) {
            $paragraphOpen = $matches[1];
            $paragraphContent = $matches[2];
            $paragraphClose = $matches[3];
            
            preg_match_all('/\{\{[^}]*(?:<[^>]*>[^<{]*)*\}\}/s', $paragraphContent, $placeholders);
            
            if (!empty($placeholders[0])) {
                foreach ($placeholders[0] as $placeholder) {
                    $cleanPlaceholder = preg_replace('/<[^>]*>/', '', $placeholder);
                    $paragraphContent = str_replace($placeholder, $cleanPlaceholder, $paragraphContent);
                }
            }
            
            return $paragraphOpen . $paragraphContent . $paragraphClose;
        },
        $xml
    );
    
    // 2. Gabungkan <w:t> yang berdekatan
    $xml = preg_replace('/(<\/w:t>)\s*(<w:t[^>]*>)/', '$1$2', $xml);
    
    // 3. Tambahan: jika ada <w:t>abc</w:t><w:t>def</w:t>, gabungkan menjadi <w:t>abcdef</w:t>
    $xml = preg_replace('/<\/w:t><w:t[^>]*>/', '', $xml);
    
    echo "Repaired: " . $xml . "\n\n";
    return $xml;
}

// Case 1: Split between { and {
testRepair('<w:p><w:r><w:t>{</w:t></w:r><w:r><w:t>{</w:t></w:r><w:r><w:t>VAR}}</w:t></w:r></w:p>');

// Case 2: Split inside the name
testRepair('<w:p><w:t>{{HASIL_</w:t><w:t>KUESIONER}}</w:t></w:p>');

// Case 3: Split with tags inside
testRepair('<w:p><w:t>{{HASIL</w:t><w:rPr><w:b/></w:rPr><w:t>_KUESIONER}}</w:t></w:p>');
