#!/usr/bin/env php
<?php

/**
 * Auto Class Diagram Generator for Laravel Project
 * 
 * Usage:
 *   php scripts/generate-class-diagram.php [options]
 * 
 * Options:
 *   --format=plantuml|mermaid|json  Output format (default: plantuml)
 *   --output=path                    Output file path
 *   --models-only                    Generate models only
 *   --services-only                  Generate services only
 *   --controllers-only               Generate controllers only
 *   --full                          Generate complete diagram
 * 
 * Examples:
 *   php scripts/generate-class-diagram.php --format=plantuml --output=docs/diagrams/models.puml --models-only
 *   php scripts/generate-class-diagram.php --format=mermaid --full
 */

class ClassDiagramGenerator
{
    private $basePath;
    private $format;
    private $output = [];
    
    public function __construct($basePath)
    {
        $this->basePath = $basePath;
        $this->format = 'plantuml'; // default
    }
    
    public function setFormat($format)
    {
        $this->format = $format;
    }
    
    /**
     * Generate diagram for Models
     */
    public function generateModels()
    {
        $modelsPath = $this->basePath . '/app/Models';
        $files = glob($modelsPath . '/*.php');
        
        $models = [];
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $models[$className] = $this->parseModel($file);
        }
        
        return $models;
    }
    
    /**
     * Generate diagram for Services
     */
    public function generateServices()
    {
        $servicesPath = $this->basePath . '/app/Services';
        $files = glob($servicesPath . '/*.php');
        
        $services = [];
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $services[$className] = $this->parseClass($file);
        }
        
        return $services;
    }
    
    /**
     * Generate diagram for Controllers
     */
    public function generateControllers()
    {
        $controllersPath = $this->basePath . '/app/Http/Controllers';
        $files = $this->getFilesRecursive($controllersPath);
        
        $controllers = [];
        foreach ($files as $file) {
            if (strpos($file, '.php') !== false) {
                $className = $this->getClassNameFromFile($file);
                $controllers[$className] = $this->parseClass($file);
            }
        }
        
        return $controllers;
    }
    
    /**
     * Parse Model file
     */
    private function parseModel($file)
    {
        $content = file_get_contents($file);
        
        return [
            'type' => 'model',
            'properties' => $this->extractProperties($content),
            'methods' => $this->extractMethods($content),
            'relationships' => $this->extractRelationships($content),
            'fillable' => $this->extractFillable($content),
            'casts' => $this->extractCasts($content),
        ];
    }
    
    /**
     * Parse generic class file
     */
    private function parseClass($file)
    {
        $content = file_get_contents($file);
        
        return [
            'type' => 'class',
            'properties' => $this->extractProperties($content),
            'methods' => $this->extractMethods($content),
            'dependencies' => $this->extractDependencies($content),
        ];
    }
    
    /**
     * Extract properties from class
     */
    private function extractProperties($content)
    {
        $properties = [];
        
        // Match: protected/private/public $variable
        preg_match_all('/\s+(protected|private|public)\s+\$(\w+)/', $content, $matches);
        
        if (!empty($matches[2])) {
            foreach ($matches[2] as $idx => $prop) {
                $properties[] = [
                    'name' => $prop,
                    'visibility' => $matches[1][$idx],
                ];
            }
        }
        
        return $properties;
    }
    
    /**
     * Extract methods from class
     */
    private function extractMethods($content)
    {
        $methods = [];
        
        // Match: public/protected/private function methodName
        preg_match_all('/(protected|private|public)\s+function\s+(\w+)\s*\(([^)]*)\)/', $content, $matches);
        
        if (!empty($matches[2])) {
            foreach ($matches[2] as $idx => $method) {
                // Skip magic methods and constructors for cleaner diagram
                if (strpos($method, '__') === 0) continue;
                
                $methods[] = [
                    'name' => $method,
                    'visibility' => $matches[1][$idx],
                    'parameters' => $this->parseParameters($matches[3][$idx]),
                ];
            }
        }
        
        return $methods;
    }
    
    /**
     * Extract Laravel relationships
     */
    private function extractRelationships($content)
    {
        $relationships = [];
        
        $relationTypes = [
            'belongsTo', 'hasOne', 'hasMany', 'belongsToMany', 
            'hasManyThrough', 'morphTo', 'morphOne', 'morphMany'
        ];
        
        foreach ($relationTypes as $type) {
            // Support both old and new syntax:
            // Old: return $this->belongsTo(User::class);
            // New: public function user(): BelongsTo { return $this->belongsTo(User::class); }
            
            // Pattern 1: Standard format
            preg_match_all("/return\s+\\\$this->{$type}\(([^)]+)\)/", $content, $matches);
            
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    // Extract model class name
                    preg_match('/[\w\\\\]+::class/', $match, $classMatch);
                    if (!empty($classMatch)) {
                        $relatedClass = str_replace('::class', '', $classMatch[0]);
                        $relatedClass = basename(str_replace('\\', '/', $relatedClass));
                        
                        $relationships[] = [
                            'type' => $type,
                            'related' => $relatedClass,
                        ];
                    }
                }
            }
        }
        
        return $relationships;
    }
    
    /**
     * Extract fillable fields
     */
    private function extractFillable($content)
    {
        preg_match('/protected\s+\$fillable\s*=\s*\[([\s\S]*?)\];/', $content, $matches);
        
        if (!empty($matches[1])) {
            $fields = explode(',', $matches[1]);
            return array_map(function($field) {
                return trim(str_replace(["'", '"'], '', $field));
            }, $fields);
        }
        
        return [];
    }
    
    /**
     * Extract casts
     */
    private function extractCasts($content)
    {
        preg_match('/protected\s+\$casts\s*=\s*\[([\s\S]*?)\];/', $content, $matches);
        
        if (!empty($matches[1])) {
            $casts = [];
            preg_match_all("/'(\w+)'\s*=>\s*'(\w+)'/", $matches[1], $castMatches);
            
            if (!empty($castMatches[1])) {
                foreach ($castMatches[1] as $idx => $field) {
                    $casts[$field] = $castMatches[2][$idx];
                }
            }
            
            return $casts;
        }
        
        return [];
    }
    
    /**
     * Extract class dependencies (constructor injection)
     */
    private function extractDependencies($content)
    {
        $dependencies = [];
        
        // Match constructor parameters with type hints
        preg_match('/__construct\((.*?)\)/', $content, $matches);
        
        if (!empty($matches[1])) {
            preg_match_all('/(\w+)\s+\$\w+/', $matches[1], $depMatches);
            
            if (!empty($depMatches[1])) {
                $dependencies = $depMatches[1];
            }
        }
        
        return $dependencies;
    }
    
    /**
     * Parse method parameters
     */
    private function parseParameters($paramString)
    {
        if (empty(trim($paramString))) return [];
        
        $params = explode(',', $paramString);
        $parsed = [];
        
        foreach ($params as $param) {
            $param = trim($param);
            if (preg_match('/(\w+)\s+\$(\w+)/', $param, $match)) {
                $parsed[] = $match[1] . ' ' . $match[2];
            } elseif (preg_match('/\$(\w+)/', $param, $match)) {
                $parsed[] = $match[1];
            }
        }
        
        return $parsed;
    }
    
    /**
     * Get files recursively
     */
    private function getFilesRecursive($dir)
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $files[] = $file->getPathname();
            }
        }
        
        return $files;
    }
    
    /**
     * Get class name from file path
     */
    private function getClassNameFromFile($file)
    {
        $relativePath = str_replace($this->basePath . '/app/', '', $file);
        $relativePath = str_replace('.php', '', $relativePath);
        return str_replace('/', '\\', $relativePath);
    }
    
    /**
     * Generate PlantUML output
     */
    public function generatePlantUML($data, $title = 'Class Diagram')
    {
        $output = "@startuml\n";
        $output .= "title {$title}\n\n";
        
        // Generate classes
        foreach ($data as $className => $info) {
            $output .= $this->generatePlantUMLClass($className, $info);
        }
        
        // Generate relationships
        $output .= "\n' Relationships\n";
        foreach ($data as $className => $info) {
            if (!empty($info['relationships'])) {
                foreach ($info['relationships'] as $rel) {
                    $output .= $this->generatePlantUMLRelationship($className, $rel);
                }
            }
        }
        
        $output .= "\n@enduml\n";
        return $output;
    }
    
    /**
     * Generate PlantUML class definition
     */
    private function generatePlantUMLClass($className, $info)
    {
        $output = "class {$className} {\n";
        
        // Properties
        if (!empty($info['fillable'])) {
            foreach ($info['fillable'] as $field) {
                $output .= "  +{$field}\n";
            }
        } elseif (!empty($info['properties'])) {
            foreach ($info['properties'] as $prop) {
                $visibility = $this->getPlantUMLVisibility($prop['visibility']);
                $output .= "  {$visibility}{$prop['name']}\n";
            }
        }
        
        $output .= "  --\n";
        
        // Methods
        if (!empty($info['methods'])) {
            foreach (array_slice($info['methods'], 0, 10) as $method) {
                $visibility = $this->getPlantUMLVisibility($method['visibility']);
                $params = implode(', ', $method['parameters']);
                $output .= "  {$visibility}{$method['name']}({$params})\n";
            }
        }
        
        $output .= "}\n\n";
        return $output;
    }
    
    /**
     * Generate PlantUML relationship
     */
    private function generatePlantUMLRelationship($className, $rel)
    {
        $arrow = match($rel['type']) {
            'belongsTo' => '<--',
            'hasOne' => '-->',
            'hasMany' => '-->"*"',
            'belongsToMany' => '"*"--"*"',
            default => '--'
        };
        
        return "{$className} {$arrow} {$rel['related']} : {$rel['type']}\n";
    }
    
    /**
     * Get PlantUML visibility symbol
     */
    private function getPlantUMLVisibility($visibility)
    {
        return match($visibility) {
            'public' => '+',
            'protected' => '#',
            'private' => '-',
            default => '~'
        };
    }
    
    /**
     * Generate Mermaid output
     */
    public function generateMermaid($data, $title = 'Class Diagram')
    {
        $output = "```mermaid\n";
        $output .= "classDiagram\n";
        $output .= "  title {$title}\n\n";
        
        // Generate classes
        foreach ($data as $className => $info) {
            $output .= $this->generateMermaidClass($className, $info);
        }
        
        // Generate relationships
        foreach ($data as $className => $info) {
            if (!empty($info['relationships'])) {
                foreach ($info['relationships'] as $rel) {
                    $output .= $this->generateMermaidRelationship($className, $rel);
                }
            }
        }
        
        $output .= "```\n";
        return $output;
    }
    
    /**
     * Generate Mermaid class definition
     */
    private function generateMermaidClass($className, $info)
    {
        $output = "  class {$className} {\n";
        
        // Properties
        if (!empty($info['fillable'])) {
            foreach ($info['fillable'] as $field) {
                $output .= "    +{$field}\n";
            }
        }
        
        // Methods
        if (!empty($info['methods'])) {
            foreach (array_slice($info['methods'], 0, 10) as $method) {
                $visibility = $method['visibility'][0]; // first letter
                $output .= "    {$visibility}{$method['name']}()\n";
            }
        }
        
        $output .= "  }\n\n";
        return $output;
    }
    
    /**
     * Generate Mermaid relationship
     */
    private function generateMermaidRelationship($className, $rel)
    {
        $arrow = match($rel['type']) {
            'belongsTo' => '<--',
            'hasOne' => '-->',
            'hasMany' => '-->"1..*"',
            'belongsToMany' => '"*"--"*"',
            default => '--'
        };
        
        return "  {$className} {$arrow} {$rel['related']} : {$rel['type']}\n";
    }
    
    /**
     * Save output to file
     */
    public function save($content, $filename)
    {
        file_put_contents($filename, $content);
        echo "✓ Diagram saved to: {$filename}\n";
    }
}


// ============================================================================
// MAIN EXECUTION
// ============================================================================

// Parse command line arguments
$options = getopt('', [
    'format:',
    'output:',
    'models-only',
    'services-only',
    'controllers-only',
    'full'
]);

$basePath = dirname(__DIR__);
$generator = new ClassDiagramGenerator($basePath);

// Set format
$format = $options['format'] ?? 'plantuml';
$generator->setFormat($format);

// Determine output file
$defaultOutput = "docs/diagrams/class-diagram.{$format}";
$outputFile = $options['output'] ?? $defaultOutput;

// Ensure output directory exists
$outputDir = dirname($outputFile);
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0755, true);
}

echo "🔍 Scanning Laravel project...\n";

// Generate based on options
$data = [];
$title = 'Complete System Class Diagram';

if (isset($options['models-only'])) {
    echo "📦 Generating Models diagram...\n";
    $data = $generator->generateModels();
    $title = 'Domain Models';
    
} elseif (isset($options['services-only'])) {
    echo "⚙️  Generating Services diagram...\n";
    $data = $generator->generateServices();
    $title = 'Service Layer';
    
} elseif (isset($options['controllers-only'])) {
    echo "🎮 Generating Controllers diagram...\n";
    $data = $generator->generateControllers();
    $title = 'Controller Layer';
    
} else {
    echo "🌐 Generating complete diagram...\n";
    $models = $generator->generateModels();
    $services = $generator->generateServices();
    $controllers = $generator->generateControllers();
    
    $data = array_merge(
        ['MODELS' => []] + $models,
        ['SERVICES' => []] + $services,
        ['CONTROLLERS' => []] + $controllers
    );
}

echo "✓ Found " . count($data) . " classes\n";
echo "📝 Generating {$format} output...\n";

// Generate output based on format
if ($format === 'plantuml') {
    $content = $generator->generatePlantUML($data, $title);
} elseif ($format === 'mermaid') {
    $content = $generator->generateMermaid($data, $title);
} elseif ($format === 'json') {
    $content = json_encode($data, JSON_PRETTY_PRINT);
} else {
    die("❌ Unknown format: {$format}\n");
}

// Save to file
$generator->save($content, $outputFile);

echo "\n✅ Done! You can now:\n";

if ($format === 'plantuml') {
    echo "  1. View online: https://www.plantuml.com/plantuml/uml/\n";
    echo "  2. Use VSCode extension: PlantUML\n";
    echo "  3. Generate image: java -jar plantuml.jar {$outputFile}\n";
    
} elseif ($format === 'mermaid') {
    echo "  1. View on GitHub (native support)\n";
    echo "  2. Use VSCode extension: Markdown Preview Mermaid\n";
    echo "  3. View online: https://mermaid.live/\n";
}

echo "\n";
