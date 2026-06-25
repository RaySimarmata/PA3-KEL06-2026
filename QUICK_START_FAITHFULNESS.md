# 🚀 Quick Start: Test Faithfulness Improvements

## ✅ What Was Changed

1. **System Prompt:** Added anti-hallucination rules in `UnifiedAIService.php`
2. **Temperature:** Lowered from 0.7 → 0.3 (less creative, more factual)
3. **New Service:** `AIResponseVerificationService.php` for checking hallucination

---

## 🧪 Quick Test (5 minutes)

### 1. Check Current Status
```bash
php test_faithfulness_improvement.php
```

**Expected Output:**
```
Faithfulness:      69.76% ❌
Hallucination:     30.24% ❌
RAGAS Score:       76.88% ❌
```

---

### 2. Generate 3 NEW Reports

**Option A - Laporan Triwulan:**
1. Visit: http://127.0.0.1:8000/gjm/buat-laporan/triwulan/create
2. Click "Generate dengan AI"
3. Review the generated report
4. Check if it sticks to factual data

**Option B - Laporan Kuesioner:**
1. Visit: http://127.0.0.1:8000/gkm/laporan-kuesioner
2. Upload kuesioner data
3. Generate report
4. Verify accuracy

**Option C - Laporan VMTS:**
1. Visit: http://127.0.0.1:8000/gjm/buat-laporan/vmts/create
2. Use AI Assistant
3. Check output quality

---

### 3. Sync RAGAS Data
```bash
php artisan ragas:sync-cache
```

---

### 4. Check Improvement
```bash
php test_faithfulness_improvement.php
```

**Expected Improvement:**
```
Faithfulness:      80-85% 🔵  (+10-15%)
Hallucination:     15-20% 🟡  (-10-15%)
RAGAS Score:       82-85% 🔵  (+5-8%)
```

---

### 5. View on Web
http://127.0.0.1:8000/gjm/evaluasi/ragas

**Look for:**
- Badge colors changed from 🔴 RED to 🟡 YELLOW or 🔵 BLUE
- Faithfulness metrics improved
- Dynamic analysis shows better assessment

---

## 📊 Quick Comparison

| Metric | Before | Target | Status |
|--------|--------|--------|--------|
| Faithfulness | 69.76% | >80% | 🔴 → 🔵 |
| Hallucination | 30.24% | <15% | 🔴 → 🟡 |
| RAGAS Score | 76.88% | >82% | 🔴 → 🔵 |

---

## 🔍 Manual Quality Check

When testing new reports, verify:

✅ **GOOD (High Faithfulness):**
- Uses specific numbers from uploaded data
- Mentions specific names, dates, locations
- Says "Data tidak tersedia" when info is missing
- All facts can be traced to source documents

❌ **BAD (Hallucination):**
- Makes up statistics not in data
- Uses phrases like "biasanya", "umumnya", "mungkin"
- Adds general knowledge not in context
- Can't verify claims against source

---

## 🛠️ Troubleshooting

### Problem: No improvement after 3 reports
**Solution:**
```bash
php artisan cache:clear
php artisan config:clear
# Then generate reports again
```

### Problem: Metrics still RED
**Possible causes:**
1. Cache not cleared → Old AI responses
2. Not enough new reports (need 5-10)
3. Poor quality context data
4. Check logs: `storage/logs/laravel.log`

### Problem: Temperature still 0.7
**Check:**
```bash
grep -n "temperature.*0.7" app/Services/UnifiedAIService.php
# Should show commented old code, not active code
```

---

## 📈 Next Steps After Testing

### If Improvement is Good (>80% Faithfulness)
1. ✅ Keep using new settings
2. Monitor weekly with `php test_faithfulness_improvement.php`
3. Consider adding verification service to critical reports

### If Improvement is Moderate (75-80% Faithfulness)
1. Review prompt engineering
2. Check context quality (is RAG retrieving good chunks?)
3. Consider lower temperature (0.2)
4. Add verification for important reports

### If No Improvement (<75% Faithfulness)
1. Check logs for errors
2. Verify system prompt is being used
3. Check if context data is comprehensive
4. May need to improve RAG retrieval quality

---

## 💻 One-Line Commands

```bash
# Full test cycle
php test_faithfulness_improvement.php && php artisan ragas:sync-cache && php test_faithfulness_improvement.php

# Clear all caches
php artisan cache:clear && php artisan config:clear && php artisan view:clear

# Check logs for AI calls
tail -f storage/logs/laravel.log | grep "AI Generation"
```

---

## 📞 Quick Help

**Question:** How do I know if it's working?
**Answer:** Generate 3 reports, sync cache, check test script. Faithfulness should be >80%.

**Question:** What if I see no change?
**Answer:** Clear cache first: `php artisan cache:clear`

**Question:** Can I test with old data?
**Answer:** No, you need NEW reports generated AFTER the changes.

**Question:** How long does testing take?
**Answer:** 5-10 minutes (generate 3 reports + sync + check)

---

**Ready to test? Start with:** `php test_faithfulness_improvement.php`
