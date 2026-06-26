# CLASS DIAGRAM GENERATION TOOLS GUIDE

**Panduan Lengkap Tools untuk Auto-Generate Class Diagram**

---

## 📋 DAFTAR ISI

1. [Script Generator Bawaan](#script-generator-bawaan)
2. [Tools Online](#tools-online)
3. [Desktop Applications](#desktop-applications)
4. [VSCode Extensions](#vscode-extensions)
5. [Command Line Tools](#command-line-tools)
6. [Comparison & Recommendations](#comparison--recommendations)

---

## 1. SCRIPT GENERATOR BAWAAN

### 🚀 Laravel Class Diagram Generator (Custom)

**Lokasi:** `scripts/generate-class-diagram.php`

#### Cara Penggunaan

```bash
# Generate semua (Models, Services, Controllers)
php scripts/generate-class-diagram.php --full

# Generate Models saja
php scripts/generate-class-diagram.php --models-only --format=plantuml

# Generate Services saja
php scripts/generate-class-diagram.php --services-only --format=mermaid

# Generate dengan output custom
php scripts/generate-class-diagram.php --models-only --output=docs/diagrams/models.puml

# Generate JSON format untuk processing lanjut
php scripts/generate-class-diagram.php --full --format=json --output=class-data.json
```

#### Supported Formats

1. **PlantUML** (`.puml`)
   - Industry standard
   - Bisa generate image (PNG, SVG, PDF)
   - Support relationship yang kompleks

2. **Mermaid** (`.mmd`)
   - Native support di GitHub
   - Bisa embed di Markdown
   - Simpler syntax

3. **JSON** (`.json`)
   - Raw data
   - Bisa diproses lebih lanjut
   - Integration dengan tools lain

#### Features

✅ Auto-detect Laravel Models  
✅ Extract Eloquent relationships  
✅ Extract fillable fields  
✅ Extract casts  
✅ Parse Services & Controllers  
✅ Detect constructor dependencies  
✅ Generate PlantUML & Mermaid  
✅ Export JSON format  

---

## 2. TOOLS ONLINE

### 🌐 PlantUML Online Server

**URL:** https://www.plantuml.com/plantuml/uml/

**Cara Pakai:**
1. Generate PlantUML dengan script kita
2. Copy isi file `.puml`
3. Paste ke web
4. Download sebagai PNG/SVG/PDF

**Kelebihan:**
- ✅ Gratis
- ✅ Tidak perlu install
- ✅ Support semua PlantUML syntax
- ✅ Export berbagai format

**Kekurangan:**
- ❌ Perlu koneksi internet
- ❌ Data diagram di-upload ke server

---

### 🌊 Mermaid Live Editor

**URL:** https://mermaid.live/

**Cara Pakai:**
1. Generate Mermaid dengan script
2. Copy isi file `.mmd`
3. Paste dan edit online
4. Download PNG/SVG

**Kelebihan:**
- ✅ Real-time preview
- ✅ Modern UI
- ✅ GitHub compatible
- ✅ Export SVG/PNG

---

### 🎨 Diagrams.net (Draw.io) Alternative

**URL:** https://app.diagrams.net/

**Cara Pakai:**
1. Import from text
2. Auto-layout
3. Manual refinement

**Kelebihan:**
- ✅ Powerful editing
- ✅ Cloud storage integration
- ✅ Collaborative editing
- ✅ Export banyak format

**Kekurangan:**
- ❌ Manual editing needed
- ❌ Not fully automated

---

## 3. DESKTOP APPLICATIONS

### 🖥️ PlantUML Desktop

**Download:** https://plantuml.com/download

**Installation:**
```bash
# Butuh Java
# Windows
choco install plantuml

# Mac
brew install plantuml

# Linux
apt install plantuml
```

**Usage:**
```bash
# Generate image dari .puml file
java -jar plantuml.jar docs/diagrams/models.puml

# Generate PNG
plantuml -tpng docs/diagrams/models.puml

# Generate SVG (recommended for documentation)
plantuml -tsvg docs/diagrams/models.puml

# Watch mode (auto-regenerate on save)
plantuml -tsvg -o output/ docs/diagrams/*.puml -watch
```

---

### 🎯 Visual Paradigm Community Edition

**URL:** https://www.visual-paradigm.com/download/community.jsp

**Features:**
- Reverse engineering dari code
- UML 2.5 compliant
- Export ke berbagai format
- Database ERD generation

**Cara Pakai:**
1. Tools → Code → Instant Reverse
2. Select Laravel project folder
3. Auto-generate class diagram
4. Customize layout

**Kelebihan:**
- ✅ Professional grade
- ✅ Auto reverse engineering
- ✅ Database ERD
- ✅ Team collaboration

**Kekurangan:**
- ❌ Heavy application
- ❌ Steep learning curve
- ❌ Limited in free version

---

### 🔷 StarUML

**URL:** https://staruml.io/

**Features:**
- Reverse engineering
- Forward engineering
- Multiple diagram types
- Extension support

**Installation:**
```bash
# Windows
choco install staruml

# Mac
brew install --cask staruml
```

**PHP Extension:**
Install "PHP Extension" untuk Laravel support

**Cara Pakai:**
1. File → Import → PHP Code
2. Select app/ folder
3. Auto-generate diagrams
4. Export PNG/SVG/PDF

---

## 4. VSCODE EXTENSIONS

### 📦 PlantUML Extension

**Extension ID:** `jebbs.plantuml`

**Installation:**
```bash
code --install-extension jebbs.plantuml
```

**Features:**
- Preview PlantUML dalam VSCode
- Auto-completion
- Export PNG/SVG
- Real-time preview

**Keybindings:**
- `Alt + D`: Preview diagram
- `Ctrl + Shift + P` → "PlantUML: Export Current Diagram"

---

### 🔮 Mermaid Preview

**Extension ID:** `vstirbu.vscode-mermaid-preview`

**Features:**
- Live preview
- GitHub style rendering
- PNG export

---

### 🎨 Draw.io Integration

**Extension ID:** `hediet.vscode-drawio`

**Features:**
- Edit .drawio files dalam VSCode
- Export PNG/SVG
- Live collaboration

---

## 5. COMMAND LINE TOOLS

### 🛠️ PHP Code Analyzer + Diagram Generator

#### Option 1: PHP_CodeSniffer dengan Custom Sniff

```bash
composer require --dev squizlabs/php_codesniffer
composer require --dev phpstan/phpstan
```

#### Option 2: phpDocumentor

```bash
# Install phpDocumentor
wget https://phpdoc.org/phpDocumentor.phar
chmod +x phpDocumentor.phar

# Generate documentation + diagrams
./phpDocumentor.phar -d app/ -t docs/api --template="clean"
```

#### Option 3: doxygen + PHP

```bash
# Install doxygen
apt install doxygen graphviz

# Create Doxyfile
doxygen -g

# Edit Doxyfile
# Set: INPUT = app/
# Set: RECURSIVE = YES
# Set: EXTRACT_ALL = YES
# Set: GENERATE_LATEX = NO
# Set: HAVE_DOT = YES

# Generate
doxygen Doxyfile
```

---

## 6. COMPARISON & RECOMMENDATIONS

### 📊 Quick Comparison Table

| Tool | Type | Automation | Cost | Learning Curve | Best For |
|------|------|------------|------|----------------|----------|
| **Custom Script** | CLI | ★★★★★ | Free | ★★☆☆☆ | Quick generation |
| **PlantUML** | CLI/Web | ★★★★☆ | Free | ★★★☆☆ | Documentation |
| **Mermaid** | Web/MD | ★★★★☆ | Free | ★★☆☆☆ | GitHub docs |
| **Visual Paradigm** | Desktop | ★★★★★ | Paid | ★★★★☆ | Professional |
| **StarUML** | Desktop | ★★★★☆ | Paid | ★★★☆☆ | UML standard |
| **Draw.io** | Web | ★★☆☆☆ | Free | ★★☆☆☆ | Manual editing |
| **phpDocumentor** | CLI | ★★★☆☆ | Free | ★★★☆☆ | Full docs |

### 🎯 Recommendations

#### For Daily Development
```bash
# Use our custom script
php scripts/generate-class-diagram.php --models-only --format=mermaid

# View in GitHub
# Mermaid auto-renders in GitHub markdown
```

#### For Documentation
```bash
# Generate PlantUML
php scripts/generate-class-diagram.php --full --format=plantuml

# Convert to PNG
plantuml -tpng docs/diagrams/*.puml

# Or SVG (better quality)
plantuml -tsvg docs/diagrams/*.puml
```

#### For Presentation
1. Use **Visual Paradigm** for professional look
2. Import from code
3. Customize layout & colors
4. Export high-resolution PNG/PDF

#### For Thesis/Academic
1. Generate PlantUML
2. Customize dengan editor
3. Generate PDF dengan high DPI
```bash
plantuml -tpdf -SDPI=300 diagram.puml
```

---

## 📝 WORKFLOW RECOMMENDATION

### Workflow 1: Quick & Simple

```bash
# 1. Generate Mermaid
php scripts/generate-class-diagram.php --models-only --format=mermaid

# 2. Embed di README.md
# GitHub akan auto-render

# 3. Done!
```

### Workflow 2: Professional Documentation

```bash
# 1. Generate PlantUML
php scripts/generate-class-diagram.php --full --format=plantuml \
    --output=docs/diagrams/complete.puml

# 2. Generate image
plantuml -tsvg docs/diagrams/complete.puml

# 3. Include di documentation
# ![Class Diagram](docs/diagrams/complete.svg)
```

### Workflow 3: Interactive & Editable

```bash
# 1. Generate JSON
php scripts/generate-class-diagram.php --full --format=json \
    --output=class-data.json

# 2. Import ke Visual Paradigm atau StarUML
# 3. Auto-layout & customize
# 4. Export ke berbagai format
```

---

## 🔧 SETUP INSTRUCTIONS

### Install PlantUML di Windows

```powershell
# Install Java jika belum
winget install Oracle.JavaRuntimeEnvironment

# Download PlantUML
curl -O https://github.com/plantuml/plantuml/releases/download/v1.2024.0/plantuml-1.2024.0.jar

# Rename untuk convenience
mv plantuml-*.jar plantuml.jar

# Test
java -jar plantuml.jar -version

# Create alias (optional)
# Add to PowerShell profile
echo 'function plantuml { java -jar C:\path\to\plantuml.jar $args }' >> $PROFILE
```

### Install di Linux/Mac

```bash
# Install Java
sudo apt install default-jre graphviz  # Linux
brew install openjdk graphviz          # Mac

# Install PlantUML
sudo apt install plantuml              # Linux
brew install plantuml                  # Mac

# Verify
plantuml -version
```

---

## 🎨 ADVANCED: Custom Styling

### PlantUML Skin Parameters

Create `styles/diagram-theme.puml`:

```plantuml
@startuml
!define LIGHTORANGE
skinparam backgroundColor #FEFEFE
skinparam handwritten false

skinparam class {
    BackgroundColor<<Model>> LightBlue
    BackgroundColor<<Service>> LightGreen
    BackgroundColor<<Controller>> LightYellow
    BorderColor Black
    ArrowColor Black
}

skinparam stereotypeCBackgroundColor YellowGreen
skinparam classAttributeIconSize 0

@enduml
```

Use in your diagrams:
```plantuml
@startuml
!include styles/diagram-theme.puml

class User <<Model>> {
    +id: int
    +name: string
}

@enduml
```

---

## 📚 ADDITIONAL RESOURCES

### Learning Resources

- **PlantUML Guide:** https://plantuml.com/guide
- **Mermaid Docs:** https://mermaid-js.github.io/mermaid/
- **UML Tutorial:** https://www.uml-diagrams.org/

### Community

- **PlantUML Forum:** https://forum.plantuml.net/
- **Stack Overflow:** Tag `plantuml`, `mermaid`, `class-diagram`

---

## 🆘 TROUBLESHOOTING

### Issue: "Java not found"

```bash
# Check Java installation
java -version

# Install Java
# Windows: winget install Oracle.JavaRuntimeEnvironment
# Mac: brew install openjdk
# Linux: sudo apt install default-jre
```

### Issue: "Graphviz not found"

```bash
# PlantUML needs Graphviz for complex diagrams

# Windows
choco install graphviz

# Mac
brew install graphviz

# Linux
sudo apt install graphviz
```

### Issue: "PHP memory limit exceeded"

Edit `php.ini`:
```ini
memory_limit = 512M
```

Or run with:
```bash
php -d memory_limit=512M scripts/generate-class-diagram.php
```

---

**Happy Diagramming! 🎉**
