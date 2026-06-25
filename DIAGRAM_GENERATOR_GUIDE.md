# 🎨 AUTO CLASS DIAGRAM GENERATOR - QUICK START

**Generate UML Class Diagrams otomatis dari Laravel code!**

---

## ⚡ QUICK START (5 Menit)

### Method 1: Windows (Batch Script)

```cmd
REM Double click atau run:
scripts\generate-diagrams.bat
```

Pilih menu yang muncul!

### Method 2: Command Line

```bash
# Generate semua diagram
php scripts/generate-class-diagram.php --full

# Output: docs/diagrams/class-diagram.plantuml
```

### Method 3: Models Only

```bash
php scripts/generate-class-diagram.php --models-only
```

---

## 📦 WHAT YOU GET

Script ini akan generate:

```
docs/
└── diagrams/
    ├── class-diagram.plantuml   ← Complete diagram
    ├── models.puml              ← Models only  
    ├── services.puml            ← Services only
    └── controllers.puml         ← Controllers only
```

---

## 👀 HOW TO VIEW

### Option 1: Online (Easiest)

1. Open https://www.plantuml.com/plantuml/uml/
2. Copy-paste isi file `.puml`
3. Done! View & download

### Option 2: VSCode

```bash
# Install extension
code --install-extension jebbs.plantuml

# Open .puml file
# Press Alt + D
```

### Option 3: GitHub

```bash
# Use Mermaid format instead
php scripts/generate-class-diagram.php --full --format=mermaid

# GitHub auto-renders Mermaid in markdown!
```

---

## 🎯 COMMON USE CASES

### For Daily Development

```bash
# After adding new models
php scripts/generate-class-diagram.php --models-only

# Preview in VSCode
code docs/diagrams/class-diagram.plantuml
# Press Alt + D
```

### For Documentation

```bash
# Generate SVG for docs
php scripts/generate-class-diagram.php --full

# Convert to SVG
plantuml -tsvg docs/diagrams/class-diagram.puml

# Include in README.md
# ![Architecture](docs/diagrams/class-diagram.svg)
```

### For Thesis/Paper

```bash
# Generate high-res PDF
php scripts/generate-class-diagram.php --full
plantuml -tpdf -SDPI=300 docs/diagrams/class-diagram.puml
```

---

## 🛠️ TOOLS COMPARISON

| Need | Tool | Command |
|------|------|---------|
| **Quick view** | PlantUML Online | Copy-paste → https://plantuml.com |
| **Daily dev** | VSCode + Extension | `code --install-extension jebbs.plantuml` |
| **GitHub docs** | Mermaid | `--format=mermaid` |
| **Presentation** | Draw.io | Import JSON → https://app.diagrams.net |
| **Professional** | Visual Paradigm | Reverse engineer from code |

**Full comparison:** See `docs/TOOLS_COMPARISON.md`

---

## 📚 DOCUMENTATION

| File | Description |
|------|-------------|
| `scripts/README.md` | Script usage guide |
| `docs/CLASS_DIAGRAM_TOOLS_GUIDE.md` | Complete tools guide |
| `docs/TOOLS_COMPARISON.md` | Tools comparison |
| `docs/CLASS_DIAGRAM_DOCUMENTATION.md` | Main architecture docs |

---

## 🔧 REQUIREMENTS

### Minimal
- PHP 8.2+ (sudah ada di project)

### Optional (untuk generate image)
- **Java** (untuk PlantUML)
- **Graphviz** (untuk complex diagrams)

### Installation

#### Windows
```powershell
# Install Java
winget install Oracle.JavaRuntimeEnvironment

# Install PlantUML
choco install plantuml graphviz
```

#### Mac
```bash
brew install openjdk plantuml graphviz
```

#### Linux
```bash
sudo apt install default-jre plantuml graphviz
```

---

## 🎨 OUTPUT FORMATS

### PlantUML (Recommended)
```bash
php scripts/generate-class-diagram.php --full --format=plantuml
```

✅ Industry standard  
✅ High quality exports  
✅ Professional look  

### Mermaid (GitHub-friendly)
```bash
php scripts/generate-class-diagram.php --full --format=mermaid
```

✅ Native GitHub support  
✅ Simple syntax  
✅ No build needed  

### JSON (For processing)
```bash
php scripts/generate-class-diagram.php --full --format=json
```

✅ Machine readable  
✅ Custom tools  
✅ Further processing  

---

## 💡 PRO TIPS

### Tip 1: Separate Diagrams
```bash
# Don't put everything in one diagram
# Generate separate diagrams for clarity

php scripts/generate-class-diagram.php --models-only --output=docs/diagrams/models.puml
php scripts/generate-class-diagram.php --services-only --output=docs/diagrams/services.puml
```

### Tip 2: Auto-Update with Git Hooks
Create `.git/hooks/post-commit`:
```bash
#!/bin/bash
# Auto-generate diagram after commit
php scripts/generate-class-diagram.php --models-only --format=mermaid
git add docs/diagrams/
git commit --amend --no-edit
```

### Tip 3: CI/CD Integration
```yaml
# .github/workflows/diagrams.yml
name: Update Diagrams
on: [push]
jobs:
  generate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Generate
        run: php scripts/generate-class-diagram.php --full --format=mermaid
      - name: Commit
        run: |
          git add docs/diagrams/
          git commit -m "docs: update diagrams" || echo "No changes"
          git push
```

---

## 🐛 TROUBLESHOOTING

### Script Not Found
```bash
# Make sure you're in project root
cd /path/to/PA3-KEL06-2026
php scripts/generate-class-diagram.php --full
```

### Memory Limit Error
```bash
# Increase PHP memory
php -d memory_limit=512M scripts/generate-class-diagram.php --full
```

### PlantUML Not Generating
```bash
# Check Java installation
java -version

# If not installed:
# Windows: winget install Oracle.JavaRuntimeEnvironment
# Mac: brew install openjdk
# Linux: sudo apt install default-jre
```

### Graphviz Error
```bash
# Install Graphviz
# Windows: choco install graphviz
# Mac: brew install graphviz
# Linux: sudo apt install graphviz
```

---

## 🎯 RECOMMENDED WORKFLOW

### For Individual Developer
```
1. Write code
2. Run: php scripts/generate-class-diagram.php --models-only
3. Preview in VSCode (Alt + D)
4. Commit diagram source (.puml)
```

### For Team
```
1. Write code
2. Run: php scripts/generate-class-diagram.php --full --format=mermaid
3. Commit to GitHub
4. GitHub auto-renders in README
5. Team sees updated diagram automatically
```

### For Documentation
```
1. Run: php scripts/generate-class-diagram.php --full
2. Generate image: plantuml -tsvg docs/diagrams/*.puml
3. Include in docs: ![](docs/diagrams/class-diagram.svg)
4. Commit both source and image
```

---

## 🚀 NEXT STEPS

1. ✅ Generate your first diagram
   ```bash
   php scripts/generate-class-diagram.php --models-only
   ```

2. ✅ View it online
   - Go to https://www.plantuml.com/plantuml/uml/
   - Paste content
   - View!

3. ✅ Install VSCode extension (optional)
   ```bash
   code --install-extension jebbs.plantuml
   ```

4. ✅ Read full documentation
   - `docs/CLASS_DIAGRAM_TOOLS_GUIDE.md`
   - `docs/TOOLS_COMPARISON.md`

---

## 📧 SUPPORT

- **GitHub Issues:** Create issue di repository
- **Documentation:** See `docs/` folder
- **Script Help:** `php scripts/generate-class-diagram.php --help`

---

## 🎉 EXAMPLES

### Example 1: Models Diagram
```bash
php scripts/generate-class-diagram.php --models-only
```

Result: `docs/diagrams/class-diagram.plantuml`

### Example 2: GitHub README
```bash
php scripts/generate-class-diagram.php --models-only --format=mermaid
```

Embed in README.md:
````markdown
## Architecture

```mermaid
[paste generated content here]
```
````

### Example 3: Thesis PDF
```bash
php scripts/generate-class-diagram.php --full
plantuml -tpdf -SDPI=300 docs/diagrams/class-diagram.puml
```

Result: High-resolution PDF ready for print!

---

**Happy Diagramming! 🎨**

Need help? Check `docs/CLASS_DIAGRAM_TOOLS_GUIDE.md` for complete guide!
