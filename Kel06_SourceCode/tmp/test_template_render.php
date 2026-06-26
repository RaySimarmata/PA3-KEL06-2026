<?php
require __DIR__ . '/../vendor/autoload.php';

function normalizePlaceholderName(string $content): ?string
{
    $cleaned = preg_replace('/<[^>]*>/', '', $content);
    $cleaned = preg_replace('/[\s\x00A0\p{Z}]+/u', '_', $cleaned);
    $cleaned = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $cleaned);
    $cleaned = preg_replace('/_+/', '_', $cleaned);
    $cleaned = trim($cleaned, '_');
    $cleaned = strtoupper($cleaned);
    return preg_match('/^[A-Z0-9_]+$/', $cleaned) ? $cleaned : null;
}

function mergeRunsInParagraphs(string $xml): string
{
    return preg_replace_callback(
        '/<w:p[ >].*?<\/w:p>/s',
        function ($matches) {
            $paragraph = $matches[0];
            if (!preg_match_all('/<w:r(?:\s[^>]*)?>.*?<\/w:r>/s', $paragraph, $runMatches)) {
                return $paragraph;
            }

            $runs = $runMatches[0];
            $texts = [];
            foreach ($runs as $run) {
                preg_match_all('/<w:t[^>]*>(.*?)<\/w:t>/s', $run, $textMatches);
                $texts[] = implode('', $textMatches[1]);
            }

            $origCombined = implode('', $texts);
            $origTexts = $texts;

            preg_match_all('/\{\{([^}]*(?:\}(?!\})[^}]*)*)\}\}/s', $origCombined, $matches, PREG_OFFSET_CAPTURE);
            if (empty($matches[0])) {
                return $paragraph;
            }

            foreach ($matches[0] as $match) {
                $placeholderName = normalizePlaceholderName($match[0]);
                if ($placeholderName === null) {
                    continue;
                }

                $token = '${' . $placeholderName . '}';
                $tokenStart = $match[1];
                $origTokenLen = mb_strlen($match[0], 'UTF-8');
                $cumulative = 0;
                $startRun = -1;
                $endRun = -1;

                foreach ($origTexts as $index => $text) {
                    $runStart = $cumulative;
                    $runEnd = $cumulative + mb_strlen($text, 'UTF-8');
                    if ($startRun === -1 && $tokenStart < $runEnd && $tokenStart >= $runStart) {
                        $startRun = $index;
                    }
                    if ($startRun !== -1 && ($tokenStart + $origTokenLen) <= $runEnd) {
                        $endRun = $index;
                        break;
                    }
                    $cumulative = $runEnd;
                }

                if ($startRun === -1 || $endRun === -1) {
                    continue;
                }

                $runs[$startRun] = preg_replace(
                    '/<w:t[^>]*>.*?<\/w:t>/s',
                    '<w:t xml:space="preserve">' . $token . '</w:t>',
                    $runs[$startRun],
                    1
                );
                $runs[$startRun] = preg_replace(
                    '/(<w:t[^>]*>.*?<\/w:t>)(?:.*?<w:t[^>]*>.*?<\/w:t>)+/s',
                    '$1',
                    $runs[$startRun]
                );

                for ($i = $startRun + 1; $i <= $endRun; $i++) {
                    $runs[$i] = preg_replace('/<w:t[^>]*>.*?<\/w:t>/s', '<w:t></w:t>', $runs[$i]);
                }

                $origTexts[$startRun] = $token;
                for ($i = $startRun + 1; $i <= $endRun; $i++) {
                    $origTexts[$i] = '';
                }
                $origCombined = implode('', $origTexts);
            }

            $fixedParagraph = $paragraph;
            foreach ($runs as $index => $fixedRun) {
                $fixedParagraph = str_replace($runMatches[0][$index], $fixedRun, $fixedParagraph);
            }

            return $fixedParagraph;
        },
        $xml
    );
}

function convertDoubleBracePlaceholders(string $xml): string
{
    $xml = mergeRunsInParagraphs($xml);

    return preg_replace_callback(
        '/\{\{([^}]*(?:\}(?!\})[^}]*)*)\}\}/s',
        function ($m) {
            $content = $m[1];
            $cleaned = preg_replace('/<[^>]*>/', '', $content);
            $cleaned = preg_replace('/[\s\x00A0]+/u', '_', $cleaned);
            $cleaned = preg_replace('/[^\p{L}\p{N}_]+/u', '_', $cleaned);
            $cleaned = preg_replace('/_+/', '_', $cleaned);
            $cleaned = trim($cleaned, '_');
            $cleaned = strtoupper($cleaned);

            if (preg_match('/^[A-Z0-9_]+$/', $cleaned)) {
                return '${' . $cleaned . '}';
            }
            return $m[0];
        },
        $xml
    );
}

$path = __DIR__ . '/../storage/app/public/templates/1781059401_Template Laporan Bulanan.docx';
$zip = new ZipArchive();
$zip->open($path);
$xml = $zip->getFromName('word/document.xml');
$merged = mergeRunsInParagraphs($xml);
$hasMerged = strpos($merged, '${HAMBATAN_PENJELASAN}') !== false;
$fixed = convertDoubleBracePlaceholders($xml);
$hasFixed = strpos($fixed, '${HAMBATAN_PENJELASAN}') !== false;
$zip->close();

echo "MERGED_HAS_TOKEN=" . ($hasMerged ? 'YES' : 'NO') . "\n";
echo "FIXED_HAS_TOKEN=" . ($hasFixed ? 'YES' : 'NO') . "\n";
