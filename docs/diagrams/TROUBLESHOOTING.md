# 🔧 CLASS DIAGRAM TROUBLESHOOTING GUIDE

Common issues dan solusinya saat generate atau view class diagrams.

---

## ❌ ERROR: Request Header is Too Large (HTTP 400)

### Problem
```
HTTP Status 400 – Bad Request
Type Exception Report
Message Request header is too large
java.lang.IllegalArgumentException: Request header is too large
Apache Tomcat/9.0.112
```

### Cause
File PlantUML terlalu besar (>8-10 KB) untuk di-paste ke PlantUML online server.

### ✅ SOLUTIONS

#### Solution 1: Use File Upload (RECOMMENDED)

**PlantUML Online:**
1. Go to https://www.plantuml.com/plantuml/uml/
2. Click **"Load file"** button (top right)
3. Select your `.puml` file
4. Or drag-drop file directly to browser

**No copy-paste needed!** ✨

---

#### Solution 2: Generate Image Locally

```bash
# Install PlantUML CLI
# Windows:
choco install plantuml graphviz

# Mac:
brew install plantuml graphviz

# Linux:
sudo apt install plantuml graphviz

# Generate SVG (vector, best quality)
plantuml -tsvg docs/diagrams/models.puml

# Generate PNG
plantuml -tpng docs/diagrams/models.puml

# Generate all
plantuml -tsvg docs/diagrams/*.puml
```

**Output:** `models.svg` yang bisa dibuka di browser!

---

#### Solution 3: Use VSCode Extension

```bash
# Install extension
code --install-extension jebbs.plantuml

# Open .puml file in VSCode
# Press Alt + D to preview
# No internet needed!
```

---

#### Solution 4: Split Diagrams

Generate smaller diagrams:

```bash
# Generate per domain
php scripts/generate-class-diagram.php --models-only --output=docs/diagrams/models-core.puml

# Or manually split by removing classes from .puml file
# Keep only 10-15 classes per diagram
```

---

## 🔗 ISSUE: Classes Appear Isolated (No Relationships)

### Problem
Some models appear without any connections to other classes in diagram:
- AIEvaluationResult (berdiri sendiri)
- DocumentChunk (berdiri sendiri)  
- Reminder (berdiri sendiri)

### Cause
1. **Modern PHP Syntax** - Script tidak detect return type hints
   ```php
   // ❌ Not detected by old script
   public function template(): BelongsTo {
       return $this->belongsTo(TemplateLaporan::class);
   }
   ```

2. **No Relationships** - Model memang tidak punya relationship method

### ✅ SOLUTIONS

#### Solution 1: Use Fixed Script (DONE!)

Script sudah di-update untuk support modern PHP syntax:

```bash
# Regenerate dengan script yang sudah difix
php scripts/generate-class-diagram.php --models-only --output=docs/diagrams/models-fixed.puml
```

#### Solution 2: Manual Check

Check model file untuk verify relationship:

```bash
# Check DocumentChunk relationships
php artisan tinker
>>> App\Models\DocumentChunk::first()->template
>>> App\Models\DocumentChunk::first()->kuesioneUpload
```

#### Solution 3: Add Relationships Manually

Edit `.puml` file and add relationship:

```plantuml
' Add at bottom
DocumentChunk <-- TemplateLaporan : belongsTo
DocumentChunk <-- KuesioneUpload : belongsTo
Reminder <-- User : belongsTo
```

---

## 💾 ISSUE: Memory Exhausted

### Problem
```
PHP Fatal error: Allowed memory size exhausted
```

### Cause
Project too large, script runs out of memory.

### ✅ SOLUTIONS

#### Solution 1: Increase PHP Memory

```bash
# Temporary (one command)
php -d memory_limit=512M scripts/generate-class-diagram.php --full

# Permanent (edit php.ini)
memory_limit = 512M
```

#### Solution 2: Generate Selectively

```bash
# Don't generate everything at once
php scripts/generate-class-diagram.php --models-only
php scripts/generate-class-diagram.php --services-only
php scripts/generate-class-diagram.php --controllers-only
```

---

## 🖼️ ISSUE: PlantUML Cannot Generate Image

### Problem
```
Error: Cannot find Java
Error: Graphviz not installed
```

### Cause
Missing dependencies.

### ✅ SOLUTIONS

#### Install Java

```bash
# Check if installed
java -version

# Windows
winget install Oracle.JavaRuntimeEnvironment

# Mac
brew install openjdk

# Linux
sudo apt install default-jre
```

#### Install Graphviz

```bash
# Check if installed
dot -V

# Windows
choco install graphviz

# Mac
brew install graphviz

# Linux
sudo apt install graphviz
```

#### Verify Installation

```bash
# Test
plantuml -version
plantuml -testdot
```

---

## 📝 ISSUE: Diagram Too Complex / Messy

### Problem
Diagram terlalu ramai, susah dibaca.

### ✅ SOLUTIONS

#### Solution 1: Generate Separate Diagrams

```bash
# By layer
php scripts/generate-class-diagram.php --models-only
php scripts/generate-class-diagram.php --services-only

# By module
# Edit script or manually split
```

#### Solution 2: Customize Layout

Edit `.puml` file:

```plantuml
@startuml
!define LIGHTORANGE

' Layout hints
left to right direction
' or
top to bottom direction

' Hide methods for cleaner look
hide methods

' Show only specific relationships
hide members
@enduml
```

#### Solution 3: Use Different Tool

For complex diagrams:
- **Visual Paradigm** - Better auto-layout
- **StarUML** - Professional layout
- **Draw.io** - Manual but flexible

---

## 🔄 ISSUE: Diagram Not Updating

### Problem
Generated diagram sama terus, tidak reflect code changes.

### ✅ SOLUTIONS

#### Solution 1: Clear Cache

```bash
# Clear Laravel cache
php artisan cache:clear

# Regenerate
php scripts/generate-class-diagram.php --full --output=docs/diagrams/new.puml
```

#### Solution 2: Force Regenerate

```bash
# Delete old files
rm docs/diagrams/*.puml

# Regenerate fresh
php scripts/generate-class-diagram.php --models-only
```

#### Solution 3: Check File Timestamps

```bash
# Check when generated
ls -lh docs/diagrams/

# Compare with model timestamps
ls -lh app/Models/
```

---

## 🌐 ISSUE: Online Viewer Timeout

### Problem
PlantUML online server timeout atau lambat.

### ✅ SOLUTIONS

#### Solution 1: Use Local Preview

```bash
# VSCode extension (fastest)
code --install-extension jebbs.plantuml
# Then: Alt + D

# Or generate image locally
plantuml -tsvg diagram.puml
```

#### Solution 2: Use Alternative Server

- https://www.planttext.com/
- https://plantuml-editor.kkeisuke.com/
- https://liveuml.com/

#### Solution 3: Self-hosted PlantUML

```bash
# Docker
docker run -d -p 8080:8080 plantuml/plantuml-server

# Access at http://localhost:8080
```

---

## 📏 ISSUE: Diagram Too Large for GitHub

### Problem
GitHub doesn't render large Mermaid diagrams.

### ✅ SOLUTIONS

#### Solution 1: Use Images Instead

```bash
# Generate PNG from PlantUML
plantuml -tpng diagram.puml

# Include in README
![Architecture](docs/diagrams/models.png)
```

#### Solution 2: Split Mermaid Diagram

Create multiple smaller diagrams:
- models-user.mmd
- models-laporan.mmd
- models-monitoring.mmd

#### Solution 3: Use External Link

```markdown
## Architecture

View full diagram: [PlantUML Online](https://www.plantuml.com/plantuml/uml/[encoded-url])
```

---

## 🎨 ISSUE: Ugly Diagram Style

### Problem
Default style tidak menarik.

### ✅ SOLUTIONS

#### Solution 1: Apply Theme

Create `docs/diagrams/theme.puml`:

```plantuml
@startuml
skinparam backgroundColor #FEFEFE
skinparam shadowing false
skinparam roundcorner 10

skinparam class {
    BackgroundColor<<Model>> #E1F5FE
    BackgroundColor<<Service>> #E8F5E9
    BackgroundColor<<Controller>> #FFF9C4
    BorderColor #424242
    ArrowColor #616161
    FontName Arial
    FontSize 12
}

skinparam stereotypeCBackgroundColor YellowGreen
@enduml
```

Include in diagrams:

```plantuml
@startuml
!include theme.puml

class User {
    ...
}
@enduml
```

#### Solution 2: Use Professional Tool

- **Visual Paradigm** - Pre-built templates
- **StarUML** - Clean default style
- **Draw.io** - Manual styling

---

## 🐛 ISSUE: Script Errors

### Problem
Generator script crashes atau errors.

### ✅ SOLUTIONS

#### Check PHP Version

```bash
php -v
# Need PHP 8.2+
```

#### Check File Permissions

```bash
# Windows
icacls scripts\generate-class-diagram.php

# Linux/Mac
chmod +x scripts/generate-class-diagram.php
```

#### Run with Debug

```bash
# Verbose output
php scripts/generate-class-diagram.php --models-only 2>&1 | tee debug.log
```

#### Check Error Log

```bash
# Laravel log
tail -f storage/logs/laravel.log

# PHP error log
tail -f /var/log/php_errors.log
```

---

## 📊 QUICK REFERENCE

| Issue | Quick Fix |
|-------|-----------|
| **Header too large** | Use file upload atau generate local |
| **No relationships** | Use updated script |
| **Memory exhausted** | `php -d memory_limit=512M` |
| **Java not found** | `winget install Oracle.JavaRuntimeEnvironment` |
| **Graphviz error** | `choco install graphviz` |
| **Diagram messy** | Split into multiple diagrams |
| **GitHub timeout** | Use images instead of Mermaid |
| **Ugly style** | Apply custom theme |

---

## 🆘 STILL STUCK?

### Debug Checklist

```bash
# 1. Check PHP
php -v

# 2. Check Java (for PlantUML)
java -version

# 3. Check Graphviz
dot -V

# 4. Test generator
php scripts/generate-class-diagram.php --models-only --output=test.puml

# 5. Check output
ls -lh test.puml
cat test.puml | head -20

# 6. Test PlantUML
plantuml -testdot
```

### Get Help

1. **Read Documentation:**
   - `scripts/README.md`
   - `docs/CLASS_DIAGRAM_TOOLS_GUIDE.md`
   - `DIAGRAM_GENERATOR_GUIDE.md`

2. **Check Examples:**
   - `docs/diagrams/README.md`
   - Existing `.puml` files

3. **Alternative Tools:**
   - Try Mermaid format (simpler)
   - Use Draw.io (manual)
   - Try Visual Paradigm (professional)

---

**Last Updated:** June 15, 2026  
**Script Version:** 1.1 (with modern PHP support)
