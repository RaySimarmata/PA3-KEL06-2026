<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\TemplateLaporan;
use App\Models\LaporanBulanan;

echo "=== Templates in Database ===\n";
$templates = TemplateLaporan::all();
echo "Total templates: " . $templates->count() . "\n";
foreach ($templates as $t) {
    echo sprintf("ID=%d, nama=%s, file_path=%s, active=%s\n",
        $t->id,
        $t->nama_template ?? '[null]',
        $t->file_path ?? '[null]',
        $t->is_active ? 'yes' : 'no');
}

echo "\n=== Report #47 Template Info ===\n";
$report = LaporanBulanan::find(47);
echo "Template ID: " . ($report->template_id ?? '[null]') . "\n";
echo "Template relation result:\n";
$template = $report->template;
echo "Type: " . (is_object($template) ? get_class($template) : gettype($template)) . "\n";
if ($template instanceof \Illuminate\Database\Eloquent\Model) {
    echo "ID: " . ($template->id ?? '[null]') . "\n";
    echo "Name: " . ($template->nama_template ?? '[null]') . "\n";
} else {
    echo "Value: " . var_export($template, true) . "\n";
}
