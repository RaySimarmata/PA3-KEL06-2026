#!/bin/bash
# Script untuk cleanup model dan dependencies yang tidak digunakan
# Jalankan dengan: bash cleanup_database.sh

echo "=========================================="
echo "DATABASE CLEANUP SCRIPT"
echo "=========================================="
echo ""

# Backup branch
echo "Creating backup branch..."
git checkout -b backup-before-cleanup-$(date +%Y%m%d)
git add .
git commit -m "Backup before database cleanup"
git checkout -b cleanup-deprecated-models

echo ""
echo "Step 1: Checking dependencies for deprecated models..."
echo "=========================================="

# Array model yang akan dihapus
declare -a models=(
    "Dosen"
    "Perwaliaan"
    "MatkulDosen"
    "JabatanAkademik"
    "Monitoring"
    "Kuisioner"
    "PertanyaanKuisioner"
    "JawabanKuisioner"
    "AIResponseCache"
)

# Check usage di codebase
for model in "${models[@]}"
do
    echo ""
    echo "Checking usage of: $model"
    echo "---"
    
    # Cari di Controllers
    echo "In Controllers:"
    grep -r "use App\\\\Models\\\\$model" app/Http/Controllers/ 2>/dev/null || echo "  Not found"
    
    # Cari di Services
    echo "In Services:"
    grep -r "use App\\\\Models\\\\$model" app/Services/ 2>/dev/null || echo "  Not found"
    
    # Cari relasi di model lain
    echo "In Models (relationships):"
    grep -r "::class.*$model\|$model::class" app/Models/ 2>/dev/null || echo "  Not found"
    
    echo ""
done

echo ""
echo "Step 2: Do you want to proceed with deletion? (y/n)"
read -r response

if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]
then
    echo ""
    echo "Deleting deprecated models..."
    
    for model in "${models[@]}"
    do
        if [ -f "app/Models/$model.php" ]; then
            echo "Deleting app/Models/$model.php"
            rm "app/Models/$model.php"
        fi
    done
    
    echo ""
    echo "Models deleted successfully!"
    echo ""
    echo "Next steps:"
    echo "1. Run: composer dump-autoload"
    echo "2. Fix any broken imports in controllers/services"
    echo "3. Run tests: php artisan test"
    echo "4. Commit changes: git add . && git commit -m 'Remove deprecated models'"
    
else
    echo "Cleanup cancelled."
    git checkout main
    git branch -D cleanup-deprecated-models
fi

echo ""
echo "=========================================="
echo "Script completed!"
echo "=========================================="
