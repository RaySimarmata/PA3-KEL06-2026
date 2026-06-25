# Workflow Documentation — Quick Reference Guide

**Fast lookup for finding the right section**

---

## 🎯 Find What You Need

### I'm a Developer and I Need To...

**...implement the upload kuesioner feature**
→ Section **3.2.6.11.1** (12-step workflow with SQL examples)

**...implement the report generation feature**
→ Section **3.2.6.11.2** (12-step workflow with API examples)

**...debug why laporan generation is slow**
→ Section **3.2.6.14.1** (Troubleshooting: Hangs > 30 sec)

**...choose between OpenAI and Gemini for a task**
→ Section **3.2.6.2.4.1** (Intelligent Provider Selection)

**...implement error handling correctly**
→ Section **3.2.6.4** (Error Handling) or **3.2.6.13.2** (Anti-patterns)

**...understand multi-turn conversation**
→ Section **3.2.6.5** or **3.2.6.11.4** (Complete conversation cycle)

**...see how GKM and GJM integrate**
→ Section **3.2.6.12.1** (Data Flow Between Modules)

---

### I'm in QA/Testing and I Need To...

**...create test cases for kuesioner upload**
→ Section **3.2.6.11.1** (Each of 12 points = test cases)

**...understand what error scenarios to test**
→ Section **3.2.6.4** (Error Handling) + **3.2.6.14.1** (Common Issues)

**...verify quality metrics are calculated correctly**
→ Section **3.2.6.2.5.1** (RAGAS Evaluation Framework)

**...test the multi-turn conversation feature**
→ Section **3.2.6.11.4** (3 complete conversation turns)

---

### I'm in Operations and I Need To...

**...troubleshoot laporan generation failures**
→ Section **3.2.6.14.1** (4 common issues + solutions)

**...understand what metrics to monitor**
→ Section **3.2.6.7** (Performance Optimization) + **3.2.6.14.2** (Monitoring)

**...deploy the system to production**
→ Section **3.2.6.9** or **3.2.6.16** (Deployment procedures)

**...understand data flows for troubleshooting**
→ Section **3.2.6.12** (Integration Matrix)

**...plan for system growth**
→ Section **3.2.6.15** (Capacity Planning scenarios)

---

### I'm a Product Manager and I Need To...

**...understand the user workflow for kuesioner monitoring**
→ Section **3.2.6.11.1** (12-step workflow overview)

**...understand the report generation process**
→ Section **3.2.6.11.2** (12-step workflow overview)

**...know how GKM and GJM modules interact**
→ Section **3.2.6.12.1** (7 integration scenarios)

**...plan for future growth**
→ Section **3.2.6.15** (3 growth scenarios with actions)

**...understand deployment process & timeline**
→ Section **3.2.6.16** (4-phase deployment strategy)

---

### I'm an Architect and I Need To...

**...understand the complete system architecture**
→ Section **3.2.6.2** (7 layer breakdown)

**...review integration design**
→ Section **3.2.6.12** (Integration Matrix)

**...understand design decisions**
→ Section **3.2.6.13** (Recommended patterns + Anti-patterns)

**...plan for scalability**
→ Section **3.2.6.15** (Capacity Planning)

**...review error handling & resilience**
→ Section **3.2.6.4** (Error Handling) + **3.2.6.13** (Patterns)

---

### I'm Updating the SRS and I Need To...

**...replace sequence diagrams with text**
→ Document: **SRS_INTEGRATION_GUIDE.md** (complete instructions)

**...see the mapping of old → new sections**
→ Document: **SRS_INTEGRATION_GUIDE.md** (detailed mapping table)

**...see examples of how to replace diagrams**
→ Document: **SRS_INTEGRATION_GUIDE.md** (before/after examples)

---

## 📍 Quick Section Finder

| Topic | Primary Section | Alternative | Quick Links |
|-------|-----------------|-------------|------------|
| **Workflows** | 3.2.6.11 | 3.2.6.1-3.2.6.10 | 11.1, 11.2, 11.3, 11.4 |
| **Architecture** | 3.2.6.2 | 3.2.6.3 | 2.1-2.7 |
| **Integration** | 3.2.6.12 | 3.2.6.3 | 12.1, 12.2, 12.3 |
| **Error Handling** | 3.2.6.4 | 3.2.6.13.2 | 4.1, 4.2, 4.3 |
| **Performance** | 3.2.6.7 | 3.2.6.14.2 | 7.1, 7.2 |
| **Testing** | 3.2.6.8 | 3.2.6.14 | 8.1, 8.2 |
| **Deployment** | 3.2.6.9 | 3.2.6.16 | 9.1, 9.2 |
| **Troubleshooting** | 3.2.6.14 | 3.2.6.4 | 14.1, 14.2 |
| **Patterns** | 3.2.6.13 | 3.2.6.2 | 13.1, 13.2 |
| **Scaling** | 3.2.6.15 | 3.2.6.7 | 15.1 |
| **Security** | 3.2.6.6 | 3.2.6.2 | 6.1, 6.2 |
| **Monitoring** | 3.2.6.7.2 | 3.2.6.14.2 | 9.2 |

---

## 🔍 By Component

### User Interface Layer
→ Section **3.2.6.2.1** (UI Components)

### Agent Orchestration
→ Section **3.2.6.2.2** (Intent Parser, Route Decision, Pipeline Coordinator, State Manager, Error Handling)

### Data Processing (Spark)
→ Section **3.2.6.2.3.1** (Spark Pipeline details)

### Vector Operations (RAG)
→ Section **3.2.6.2.3.2** (Chunking, Embedding, Retrieval)

### OCR & Image Processing
→ Section **3.2.6.2.3.3** (Image extraction)

### LLM & AI Generation
→ Section **3.2.6.2.4** (Provider selection, Prompt engineering, Streaming)

### Quality Evaluation (RAGAS)
→ Section **3.2.6.2.5** (5 metrics, Quality decisions, Learning)

### Document Generation
→ Section **3.2.6.2.6** (Template processing, Multi-format export)

### Monitoring & Analytics
→ Section **3.2.6.2.7** (Performance metrics, Cost tracking)

---

## 🚀 By Workflow

### GKM Kuesioner Upload & Processing
```
Start: Section 3.2.6.11.1
├─ Point 1-3: Upload & Validation
├─ Point 4-8: Processing decision & pipeline
└─ Point 9-12: Storage & notification
Result: Processed kuesioner in MongoDB + MySQL
```

### GJM Report Generation (Interactive)
```
Start: Section 3.2.6.11.2
├─ Point 1-4: Request → File processing
├─ Point 5-9: RAG + LLM generation
├─ Point 10-11: Quality evaluation
└─ Point 12: Document export
Result: Word document + draft preview
```

### Data Sync from CIS
```
Start: Section 3.2.6.11.3
├─ Point 1-2: Trigger & connection
├─ Point 3-5: Fetch → Transform → Upsert
└─ Point 6: Cache invalidation
Result: Updated penugasan_dosen in database
```

### Multi-Turn Conversation (User Refinement)
```
Start: Section 3.2.6.11.4
├─ Turn 1: Generate initial analysis
├─ Turn 2: Refine with new focus
├─ Turn 3: Enhance with recommendations
└─ Export: Save as Word document
Result: Iteratively improved report
```

---

## ⚙️ By Technology

### Apache Spark
→ Section **3.2.6.2.3.1** (Data pipeline)

### Vector Databases
→ Section **3.2.6.2.3.2** (Chunking, Embedding, Storage, Search)

### OpenAI, Gemini, Claude
→ Section **3.2.6.2.4.1** (Provider selection) + **3.2.6.2.4.2** (Fallback chain)

### MongoDB
→ Section **3.2.6.3.4** (Cache integration) + **3.2.6.12.2** (Sync)

### MySQL
→ Section **3.2.6.3.3** (Database integration)

### Redis
→ Section **3.2.6.7.1** (Caching strategy)

### Laravel Job Queue
→ Section **3.2.6.11.1** (Point 4: Queue job decision)

### Docker & Deployment
→ Section **3.2.6.9** or **3.2.6.16** (Deployment procedure)

---

## 📊 By Metrics & Monitoring

### Response Time Performance
→ Section **3.2.6.7** (Target: <10 sec)

### Quality Metrics (RAGAS)
→ Section **3.2.6.2.5.1** (5 metrics, thresholds)

### Error Rate Monitoring
→ Section **3.2.6.14.2** (Alerting thresholds)

### Cache Performance
→ Section **3.2.6.7.1** (Hit rate targets)

### Cost Optimization
→ Section **3.2.6.2.7.2** (Provider costs)

### Availability & Reliability
→ Section **3.2.6.13.1** (Circuit Breaker pattern)

---

## 🐛 Troubleshooting Finder

### Issue: Laporan Generation Hangs
→ Section **3.2.6.14.1** (Issue 1: Debug steps + solutions)

### Issue: Quality Score Too Low
→ Section **3.2.6.14.1** (Issue 2: Debug steps + solutions)

### Issue: File Upload Fails
→ Section **3.2.6.14.1** (Issue 3: Debug steps + solutions)

### Issue: Cache Not Clearing
→ Section **3.2.6.14.1** (Issue 4: Debug steps + solutions)

### Issue: AI Provider Timeout
→ Section **3.2.6.4.1** (Provider failure handling)

### Issue: RAG No Results
→ Section **3.2.6.4.2** (RAG pipeline failure)

### Issue: Spark Job Timeout
→ Section **3.2.6.12.3** (Error propagation scenario)

---

## 💡 By Use Case

### New Feature Implementation
1. Start: **Section 3.2.6.2** (Understand architecture)
2. Plan: **Section 3.2.6.11** (Understand workflow)
3. Implement: Code per workflow points
4. Test: **Section 3.2.6.8** (Quality gates)
5. Deploy: **Section 3.2.6.16** (Deployment process)

### Incident Troubleshooting
1. Check: **Section 3.2.6.14.1** (Common issues)
2. Debug: Follow debug steps
3. Apply: Solution from documentation
4. Monitor: **Section 3.2.6.14.2** (Monitoring metrics)

### Performance Tuning
1. Identify: **Section 3.2.6.7** (Caching opportunities)
2. Implement: Caching strategy
3. Monitor: **Section 3.2.6.14.2** (Performance metrics)
4. Adjust: Tune thresholds

### Capacity Planning
1. Review: **Section 3.2.6.15** (Growth scenarios)
2. Identify: Which scenario applies
3. Plan: Actions from scenario
4. Execute: Scaling strategy

---

## 🗂️ Document Map

```
docs/3.2.6_WORKFLOW_AGENT_BASED_REPORT_ASSISTANT.md (MAIN)
├─ Sections 1-10: Architecture & Components
├─ Section 11: Workflows & Use Cases ⭐
├─ Section 12: Integration Points ⭐
├─ Section 13: Design Patterns ⭐
├─ Section 14: Troubleshooting Guide ⭐
├─ Section 15: Capacity Planning ⭐
└─ Section 16: Governance & Deployment ⭐

docs/3.2.6_EXPANSION_SUMMARY.md (GUIDE)
├─ What was added
├─ Coverage summary
└─ Usage by role

SRS_INTEGRATION_GUIDE.md (HOW-TO)
├─ How to update SRS
├─ Before/after examples
└─ Implementation checklist

WORKFLOW_DOCUMENTATION_SUMMARY.txt (OVERVIEW)
├─ What was created
├─ Metrics & statistics
└─ Next steps

WORKFLOW_QUICK_REFERENCE.md (THIS FILE)
├─ Fast lookup guide
├─ Section finder
└─ By role/component/metric
```

---

## ⚡ Quick Jumps (Top 10)

1. **I need to understand the system** → 3.2.6.2 (Architecture)
2. **I need to implement a workflow** → 3.2.6.11 (Workflows)
3. **I need to debug an issue** → 3.2.6.14 (Troubleshooting)
4. **I need to understand integration** → 3.2.6.12 (Integration)
5. **I need to deploy** → 3.2.6.16 (Governance)
6. **I need error handling examples** → 3.2.6.4 (Error Handling)
7. **I need to test something** → 3.2.6.8 (Testing)
8. **I need to optimize performance** → 3.2.6.7 (Performance)
9. **I need to understand patterns** → 3.2.6.13 (Patterns)
10. **I need to plan for growth** → 3.2.6.15 (Capacity)

---

## 📞 Get Help

**Can't find what you're looking for?**

1. Check the **Table of Contents** in 3.2.6 document
2. Search for keywords in markdown (Ctrl+F)
3. Check **3.2.6_EXPANSION_SUMMARY.md** for coverage summary
4. Review **SRS_INTEGRATION_GUIDE.md** for section mapping
5. Check this **WORKFLOW_QUICK_REFERENCE.md** for quick lookup

**Found something missing?**

1. Add to the relevant section
2. Follow existing format/style
3. Add version number at bottom of document
4. Commit to git with descriptive message

---

## 🎓 Learning Path

### For Developers (Recommended Order)
1. Start: 3.2.6.1 (Understand why agent-based)
2. Learn: 3.2.6.2 (Complete architecture)
3. Implement: 3.2.6.11 (Workflows, pick one to start)
4. Design: 3.2.6.13 (Patterns to follow)
5. Debug: 3.2.6.14 (Troubleshooting)

### For QA (Recommended Order)
1. Start: 3.2.6.2 (Understand components)
2. Learn: 3.2.6.11 (Workflows to test)
3. Design: 3.2.6.8 (Quality gates)
4. Create: Test cases from each workflow point
5. Debug: 3.2.6.14 (Troubleshooting)

### For Operations (Recommended Order)
1. Start: 3.2.6.2 (Understand system)
2. Learn: 3.2.6.12 (Data flows)
3. Monitor: 3.2.6.14.2 (What to track)
4. Deploy: 3.2.6.16 (Deployment steps)
5. Troubleshoot: 3.2.6.14.1 (Common issues)

---

**Status:** ✅ Ready to Use  
**Last Updated:** 2026-06-15  
**Format:** Markdown (for searching, version control, easy updates)

