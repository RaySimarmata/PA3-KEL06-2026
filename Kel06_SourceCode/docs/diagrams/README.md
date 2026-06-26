# 📊 Auto-Generated Class Diagrams

**Generated on:** June 15, 2026  
**Generator:** `scripts/generate-class-diagram.php`  
**Total Classes:** 116 classes (40 Models + 37 Services + 36 Controllers + 3 Other)

---

## 📁 Available Diagrams

### 1. Domain Models (`models.puml`)
**Contains:** 40 Laravel Eloquent Models

**Key Models:**
- User, Dosen, Prodi
- RPS, Materi, Monitoring
- Kuisioner, KuesioneUpload
- LaporanGJM, LaporanGKM
- DocumentChunk (Vector DB)
- AIEvaluationResult, AIResponseCache

**View:**
- 🌐 [PlantUML Online](https://www.plantuml.com/plantuml/uml/) - Copy-paste content
- 📱 VSCode: Open `models.puml` → Press `Alt + D`
- 🖼️ Generate PNG: `plantuml models.puml`

**Relationships Extracted:**
- ✅ belongsTo
- ✅ hasOne
- ✅ hasMany
- ✅ belongsToMany

---

### 2. Service Layer (`services.puml`)
**Contains:** 37 Service Classes

**Key Services:**
- **AI Services:** UnifiedAIService, ClaudeAIService, GeminiAIService
- **RAG Services:** RAGRetrievalService, VectorDatabaseService, EmbeddingService
- **Report Services:** LaporanTriwulanService, LaporanSemesterService
- **Document Services:** OCRService, TextExtractionService
- **External APIs:** ExternalAPIService, WhatsAppService

**View:**
- 🌐 [PlantUML Online](https://www.plantuml.com/plantuml/uml/)
- 📱 VSCode: Open `services.puml` → Press `Alt + D`

**Dependencies Extracted:**
- ✅ Constructor injection
- ✅ Service composition
- ✅ Interface implementations

---

### 3. Controller Layer (`controllers.puml`)
**Contains:** 36 Controllers

**Modules:**
- **GKM Controllers:** DashboardController, MonitoringRPSController, MonitoringKuesioneController
- **GJM Controllers:** LaporanTriwulanController, LaporanSemesterController, BuatPPTController
- **API Controllers:** GKM/DashboardApiController, GJM/DashboardApiController
- **Auth:** AuthController

**View:**
- 🌐 [PlantUML Online](https://www.plantuml.com/plantuml/uml/)
- 📱 VSCode: Open `controllers.puml` → Press `Alt + D`

---

### 4. Complete System (`complete.mmd`)
**Format:** Mermaid (GitHub-friendly)  
**Contains:** All 116 classes

**View:**
- 🌐 [Mermaid Live](https://mermaid.live/) - Copy-paste content
- 📘 GitHub: Auto-renders in markdown
- 📱 VSCode: Install Mermaid Preview extension

**Embed in Markdown:**
````markdown
```mermaid
[paste complete.mmd content here]
```
````

---

### 5. Raw Data (`class-data.json`)
**Format:** JSON  
**Contains:** Complete class metadata

**Structure:**
```json
{
  "ClassName": {
    "type": "model",
    "properties": [...],
    "methods": [...],
    "relationships": [...],
    "fillable": [...],
    "casts": [...]
  }
}
```

**Use Cases:**
- Custom tools integration
- Further processing
- Analysis scripts
- Documentation generation

---

## 🎨 How to View Diagrams

### Method 1: Online (Easiest)

#### PlantUML Files (.puml)
1. Open https://www.plantuml.com/plantuml/uml/
2. Copy entire content of `.puml` file
3. Paste in text area
4. View & download PNG/SVG/PDF

#### Mermaid Files (.mmd)
1. Open https://mermaid.live/
2. Copy entire content of `.mmd` file
3. Paste in editor
4. Download PNG/SVG

---

### Method 2: VSCode Extensions

#### For PlantUML
```bash
# Install extension
code --install-extension jebbs.plantuml

# Open any .puml file
# Press Alt + D to preview
```

#### For Mermaid
```bash
# Install extension
code --install-extension vstirbu.vscode-mermaid-preview

# Open .mmd file
# Auto-preview in sidebar
```

---

### Method 3: Generate Images

#### Install PlantUML CLI

**Windows:**
```powershell
# Install Java first
winget install Oracle.JavaRuntimeEnvironment

# Install PlantUML
choco install plantuml graphviz
```

**Mac:**
```bash
brew install openjdk plantuml graphviz
```

**Linux:**
```bash
sudo apt install default-jre plantuml graphviz
```

#### Generate Images

```bash
# Generate PNG
plantuml docs/diagrams/models.puml

# Generate SVG (recommended for docs)
plantuml -tsvg docs/diagrams/models.puml

# Generate PDF (high quality)
plantuml -tpdf docs/diagrams/models.puml

# Generate all
plantuml -tsvg docs/diagrams/*.puml
```

**Output:** Creates `models.svg`, `services.svg`, `controllers.svg`

---

## 🔄 Regenerate Diagrams

### After Code Changes

```bash
# Regenerate all
php scripts/generate-class-diagram.php --full --format=plantuml --output=docs/diagrams/complete.puml
php scripts/generate-class-diagram.php --full --format=mermaid --output=docs/diagrams/complete.mmd

# Regenerate specific
php scripts/generate-class-diagram.php --models-only --output=docs/diagrams/models.puml
php scripts/generate-class-diagram.php --services-only --output=docs/diagrams/services.puml
php scripts/generate-class-diagram.php --controllers-only --output=docs/diagrams/controllers.puml
```

### Windows Batch Script

```cmd
scripts\generate-diagrams.bat
```

Interactive menu will guide you!

---

## 📊 Statistics

### Class Distribution

| Layer | Count | File |
|-------|-------|------|
| **Models** | 40 | `models.puml` |
| **Services** | 37 | `services.puml` |
| **Controllers** | 36 | `controllers.puml` |
| **Other** | 3 | `complete.mmd` |
| **TOTAL** | 116 | - |

### Relationship Types Detected

- ✅ belongsTo (parent relationship)
- ✅ hasOne (one-to-one)
- ✅ hasMany (one-to-many)
- ✅ belongsToMany (many-to-many)
- ✅ hasManyThrough
- ✅ morphTo, morphOne, morphMany

### Dependencies Extracted

- ✅ Constructor injection
- ✅ Service composition
- ✅ Interface implementations
- ✅ Trait usage

---

## 🎯 Use Cases

### Use Case 1: Documentation

```bash
# Generate SVG for docs
plantuml -tsvg docs/diagrams/*.puml

# Include in README.md
echo "## Architecture" >> README.md
echo "![Models](docs/diagrams/models.svg)" >> README.md
```

### Use Case 2: GitHub Wiki

```bash
# Use Mermaid format
php scripts/generate-class-diagram.php --models-only --format=mermaid

# Embed directly in markdown
# GitHub auto-renders!
```

### Use Case 3: Presentation

```bash
# Generate high-res PDF
plantuml -tpdf -SDPI=300 docs/diagrams/models.puml

# Use in PowerPoint/Keynote
```

### Use Case 4: Thesis

1. Generate PlantUML
2. Customize styling (see `docs/CLASS_DIAGRAM_TOOLS_GUIDE.md`)
3. Generate PDF 300 DPI
4. Include in LaTeX/Word

---

## 🎨 Customization

### PlantUML Styling

Create `docs/diagrams/theme.puml`:

```plantuml
@startuml
skinparam backgroundColor #FEFEFE
skinparam class {
    BackgroundColor<<Model>> LightBlue
    BackgroundColor<<Service>> LightGreen
    BackgroundColor<<Controller>> LightYellow
    BorderColor Black
}
@enduml
```

Include in your diagrams:
```plantuml
@startuml
!include theme.puml
...
@enduml
```

### Mermaid Themes

```mermaid
%%{init: {'theme':'base', 'themeVariables': { 'primaryColor':'#ff0000'}}}%%
classDiagram
...
```

---

## 🔗 Quick Links

- 📖 [Generator Script Documentation](../../scripts/README.md)
- 🛠️ [Tools Guide](../CLASS_DIAGRAM_TOOLS_GUIDE.md)
- ⚖️ [Tools Comparison](../TOOLS_COMPARISON.md)
- 🚀 [Quick Start Guide](../../DIAGRAM_GENERATOR_GUIDE.md)
- 📚 [Main Architecture Docs](../CLASS_DIAGRAM_DOCUMENTATION.md)

---

## 📝 Notes

- **Auto-generated:** These diagrams are auto-generated from code
- **Version control:** Commit both source (.puml/.mmd) and images (.svg/.png)
- **Updates:** Run generator after major code changes
- **Quality:** PlantUML SVG recommended for documentation
- **GitHub:** Mermaid format for native rendering

---

## 🆘 Troubleshooting

### Diagram too large?

Generate separate diagrams:
```bash
php scripts/generate-class-diagram.php --models-only
php scripts/generate-class-diagram.php --services-only
php scripts/generate-class-diagram.php --controllers-only
```

### Can't generate images?

Check Java installation:
```bash
java -version
# If not installed, install Java first
```

### Memory issues?

Increase PHP memory:
```bash
php -d memory_limit=512M scripts/generate-class-diagram.php --full
```

---

**Last Updated:** June 15, 2026  
**Auto-generated by:** `scripts/generate-class-diagram.php`  
**Manual updates:** Not recommended (will be overwritten)
