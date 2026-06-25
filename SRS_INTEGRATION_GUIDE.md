# SRS Integration Guide — Using 3.2.6 Workflow as Sequence Diagram Replacement

**Purpose:** Guide how to use the Agent-Based Workflow documentation to replace traditional sequence diagrams in the Software Requirements Specification

---

## Quick Start

### The Problem with Traditional Sequence Diagrams
- ❌ Static, hard to update
- ❌ Missing decision logic
- ❌ No error handling details
- ❌ Can't show state management
- ❌ Don't include code examples
- ❌ Not suitable for multi-turn interactions

### The Solution: Point-Based Workflow Documentation
- ✅ Dynamic, easy to update in markdown
- ✅ Includes decision trees & conditional logic
- ✅ Complete error handling & recovery
- ✅ Shows state transitions explicitly
- ✅ Includes SQL, JSON, and pseudocode
- ✅ Naturally supports complex multi-turn flows

---

## Mapping: Old SRS Sections → New 3.2.6 Sections

### User Flow Diagrams

**Old SRS Section:**
```
3.2.1 - 3.2.3: Various Sequence Diagrams
├─ Upload Kuesioner Flow
├─ Process & Analyze Flow
├─ Generate Report Flow
└─ Multi-turn Refinement Flow
```

**New SRS Section (Replace with):**
```
3.2.6.11 - Workflow Scenarios & Use Cases
├─ 3.2.6.11.1: GKM Kuesioner Monitoring Workflow
│  (replaces: Upload Kuesioner + Process Flow diagrams)
│
├─ 3.2.6.11.2: GJM Report Generation Workflow
│  (replaces: Generate Report diagram)
│
├─ 3.2.6.11.3: GKM Data Sync Workflow
│  (replaces: Data Sync from CIS diagram)
│
└─ 3.2.6.11.4: Multi-Turn Conversation Workflow
   (replaces: Multi-turn Refinement diagram)
```

**Update Instructions:**
1. Remove original sequence diagrams from SRS
2. Replace with: "See **3.2.6.11 Workflow Scenarios** for detailed step-by-step workflows"
3. Add cross-reference to specific subsection (e.g., 3.2.6.11.1 for upload)

---

### Integration & Data Flow Diagrams

**Old SRS Section:**
```
3.2.4: Integration Points
├─ GKM ↔ GJM Data Flow
├─ Module Communication
└─ Database Synchronization
```

**New SRS Section (Replace with):**
```
3.2.6.12 - Integration Matrix
├─ 3.2.6.12.1: Data Flow Between Modules (7 scenarios)
│  (replaces: Module communication diagram)
│
├─ 3.2.6.12.2: Database Synchronization Points
│  (replaces: DB sync diagram)
│
└─ 3.2.6.12.3: Error Propagation & Recovery
   (replaces: Error flow diagram)
```

**Update Instructions:**
1. Remove data flow diagrams from SRS
2. Replace with: "See **3.2.6.12 Integration Matrix** for complete data flow details"
3. Add specific reference to 3.2.6.12.1 for GKM ↔ GJM flows

---

### Architecture & Design Patterns

**Old SRS Section:**
```
3.2.5: Architecture Considerations
└─ Design patterns & best practices (text only)
```

**New SRS Section (Enhance with):**
```
3.2.6.13 - Advanced Patterns & Anti-Patterns
├─ 3.2.6.13.1: Recommended Patterns ✅
│  (Circuit Breaker, Cache-Aside, Retry, Bulkhead)
│
└─ 3.2.6.13.2: Anti-Patterns to Avoid ❌
   (Blocking HTTP, Hardcoded Thresholds, etc.)
```

**Update Instructions:**
1. Expand section 3.2.5 to reference 3.2.6.13
2. Replace generic "best practices" with specific patterns
3. Add: "For detailed implementation guidance, see **3.2.6.13**"

---

### Operational & Testing Requirements

**Old SRS Section:**
```
3.3.1: Operational Requirements
├─ Error Handling (generic)
├─ Performance (metrics only)
└─ Testing (high-level)
```

**New SRS Section (Enhance with):**
```
3.2.6.4 - Error Handling & Exception Management
3.2.6.7 - Performance Optimization
3.2.6.8 - Testing & Quality Assurance
3.2.6.9 - Deployment & Rollout

3.2.6.14 - Debugging & Troubleshooting Guide
3.2.6.15 - Capacity Planning & Scalability
3.2.6.16 - Governance & Change Management
```

**Update Instructions:**
1. Keep operational sections in SRS
2. Add: "For detailed procedures, see **3.2.6** sections listed above"
3. Reference specific subsections for specific requirements

---

## Detailed Section Mapping Table

| Old SRS | Old Content | New Location | New Content |
|---------|------------|--------------|-------------|
| 3.2.1 | Upload Sequence Diagram | 3.2.6.11.1 | 12-point detailed workflow + SQL |
| 3.2.2 | Process Sequence Diagram | 3.2.6.11.1 + 3.2.6.11.3 | Data processing + sync workflow |
| 3.2.3 | Report Gen Sequence Diagram | 3.2.6.11.2 | 12-point detailed workflow + JSON |
| 3.2.4 | Integration Diagram | 3.2.6.12.1 | 7 detailed integration scenarios |
| 3.2.5 | DB Sync Diagram | 3.2.6.12.2 | 3 synchronization patterns |
| 3.2.6 | Error Flow Diagram | 3.2.6.12.3 + 3.2.6.4 | 4+ error scenarios with recovery |
| 3.2.7 | Multi-turn Diagram | 3.2.6.11.4 | 3 complete turns with state |
| 3.3.1 | Error Handling (text) | 3.2.6.4 | Comprehensive error management |
| 3.3.2 | Performance (metrics) | 3.2.6.7 | Caching strategies + optimization |
| 3.3.3 | Testing (text) | 3.2.6.8 | Quality assurance procedures |
| - | Deployment (missing) | 3.2.6.9 | 4-phase deployment strategy |
| - | Troubleshooting (missing) | 3.2.6.14 | 4 issue scenarios + solutions |
| - | Capacity (missing) | 3.2.6.15 | 3 growth scenarios |
| - | Governance (missing) | 3.2.6.16 | Deployment procedures |

---

## How to Update SRS Document

### Step 1: Identify Sequence Diagrams to Replace

**Example:** Section 3.2.3 "Report Generation Process"

**Current SRS:**
```
3.2.3 Report Generation Process

[SEQUENCE DIAGRAM showing: User → UI → Controller → Service → AI → Response]

This diagram shows the flow of report generation...
(generic description)
```

### Step 2: Create Replacement Text

**Updated SRS:**
```
3.2.3 Report Generation Process

For detailed step-by-step workflow of report generation, including 
decision logic, code examples, and error handling, refer to:

📍 **Section 3.2.6.11.2: GJM Report Generation Workflow**

The workflow covers:
• Form input and validation
• Intent parsing and routing decisions
• File processing (if attachments provided)
• RAG context retrieval
• Multi-turn conversation support
• Quality evaluation (RAGAS metrics)
• Document generation and export

Key Decision Points:
• When to process synchronously vs asynchronously
• Provider selection for AI generation
• Quality threshold for accepting responses
• Cache utilization strategy

See **3.2.6.11.2** for complete workflow with:
✓ 12 detailed implementation points
✓ SQL query examples
✓ JSON data structures
✓ Real-time streaming examples
✓ Multi-turn state management
✓ Quality metrics calculation
```

### Step 3: Add Cross-References

**In SRS Table of Contents:**
```
3.2.6 Workflow Agent-Based Report Assistant
├─ 3.2.6.11 Workflow Scenarios & Use Cases
│  ├─ 3.2.6.11.1 GKM Kuesioner Monitoring (replaces 3.2.2)
│  ├─ 3.2.6.11.2 GJM Report Generation (replaces 3.2.3)
│  ├─ 3.2.6.11.3 GKM Data Sync (replaces 3.2.5)
│  └─ 3.2.6.11.4 Multi-Turn Conversation (replaces 3.2.7)
├─ 3.2.6.12 Integration Matrix (replaces 3.2.4)
├─ 3.2.6.13 Advanced Patterns (enhances 3.2.6)
└─ ... (other sections)
```

### Step 4: Benefits Callout

**Add to SRS introduction:**
```
### Documentation Evolution

This SRS now uses **point-based workflow documentation** 
instead of traditional sequence diagrams. This approach provides:

✅ **More Detail**: Each workflow broken into 10-15 detailed steps
✅ **Better Examples**: Includes SQL, JSON, and pseudocode
✅ **Decision Logic**: Shows conditional branches and decision trees
✅ **Error Handling**: Complete error scenarios and recovery procedures
✅ **Multi-turn Support**: Shows state transitions across multiple interactions
✅ **Easy to Update**: Simple markdown format, version-controllable
✅ **Testable**: Each step can be tested independently
✅ **Operational**: Includes debugging and troubleshooting guidance

For comparison:
• Traditional sequence diagram: 1 page, high-level only
• New workflow documentation: 3-4 pages, detailed with examples
```

---

## Template for SRS Section Replacement

### Before (Old SRS)

```markdown
## 3.2.X [Flow Name]

[SEQUENCE DIAGRAM IMAGE]
Figure 3.2.X: [Flow Name]

The [flow name] process begins with [generic description]...
(limited detail, no decision logic, no error handling)
```

### After (Updated SRS)

```markdown
## 3.2.X [Flow Name]

For detailed step-by-step workflow including decision logic, error 
handling, and implementation examples, refer to:

**📍 Section 3.2.6.11.Y: [Workflow Name]**

### Workflow Overview

The [flow name] process consists of [N] main phases:

1. [Phase 1 name] - [brief description]
2. [Phase 2 name] - [brief description]
...

### Key Decision Points

- When [condition 1]: [action 1]
- When [condition 2]: [action 2]

### Error Handling

See **3.2.6.4** and **3.2.6.11.Y** for complete error scenarios

### Related Sections

- Implementation details: **3.2.6.11.Y** (specific points: X.Y.Z)
- Integration points: **3.2.6.12.1** (if applicable)
- Testing approach: **3.2.6.8** (quality assurance)
- Operations: **3.2.6.14** (troubleshooting if issues occur)
```

---

## Benefits Summary

### For SRS Authors
- **Reduced Complexity**: Replace 8-10 diagram files with 1 markdown document
- **Easier Updates**: No need for diagram tools, just edit markdown
- **Better Traceability**: Point-based structure easier to reference in tickets
- **More Comprehensive**: Can include code examples, error scenarios

### For Developers
- **Clearer Implementation**: Step-by-step points vs abstract diagram
- **Code Examples**: SQL, JSON, pseudocode reduces ambiguity
- **Decision Logic**: Explicit if/then/else conditions
- **Testing Guide**: Each point can be unit tested

### For QA/Testing
- **Test Case Mapping**: Each point = one or more test cases
- **Error Scenarios**: Documented error flows to test
- **Edge Cases**: Conditional logic reveals edge cases
- **Acceptance Criteria**: Clear "done" criteria for each step

### For Operations
- **Troubleshooting**: Debugging guide replaces post-mortems
- **Monitoring**: Metrics and thresholds pre-defined
- **Runbooks**: Deployment and rollback procedures clear
- **Capacity Planning**: Growth scenarios documented

### For Product Owners
- **Requirements Traceability**: Each workflow can be traced to user stories
- **Scope Management**: Workflow architecture prevents scope creep
- **Change Impact**: Easy to see impact of changes on workflows
- **Stakeholder Communication**: More understandable than diagrams for non-technical

---

## Implementation Checklist

### Phase 1: Documentation Review (Current)
- ✅ Review 3.2.6 workflow documentation
- ✅ Identify gaps vs current SRS
- ✅ Get stakeholder buy-in

### Phase 2: SRS Update (Next)
- [ ] Create working document (copy current SRS)
- [ ] Identify all sequence diagrams to replace
- [ ] Map old sections → new 3.2.6 sections
- [ ] Update each section with cross-references
- [ ] Add benefits callout to introduction
- [ ] Update table of contents

### Phase 3: Validation (Next)
- [ ] Have development team review
- [ ] Have QA team validate coverage
- [ ] Have operations team review procedures
- [ ] Have product owner sign off

### Phase 4: Publication (Next)
- [ ] Archive old SRS version (for reference)
- [ ] Publish updated SRS
- [ ] Communicate changes to team
- [ ] Train team on new structure

---

## Example Walkthrough: Replacing a Sequence Diagram

### Original SRS Section 3.2.2

**Original:**
```
### 3.2.2 Data Processing Flow

[DIAGRAM: Upload → Parse → Process → Store]

When user uploads kuesioner file:
1. File is uploaded to server
2. File is parsed
3. Data is processed
4. Results are stored

(~100 words, high-level only)
```

**Problems:**
- What about validation? Not shown
- What if parsing fails? No error handling
- Is processing synchronous or async? Not clear
- Where is data stored? Not specified
- How long does it take? Not mentioned

### Updated SRS Section 3.2.11.1

**Updated:**
```
### 3.2.6.11.1 GKM Kuesioner Monitoring Workflow

For complete workflow details including error handling, decision logic,
and implementation examples, see **3.2.6.11.1** which covers:

**Phase 1: Initiation & Input**
- User opens Upload page (GKM → Monitoring Kuesioner)
- Form displayed with file input

**Phase 2: Validation**
- Check file type (xlsx, xls, csv)
- Check file size (< 10MB)
- Validate content (header row exists)
- Result: Accept or Reject

**Phase 3: Decision Point**
- If file < 1000 rows: Process synchronously
- If file >= 1000 rows: Queue to async job
- Return appropriate response to user

**Phase 4: Data Processing** (Spark Pipeline)
- Ingest data from uploaded file
- Clean: normalize prodi, extract dosen
- Transform: convert Likert scale
- Aggregate: calculate statistics
- Output: structured data for RAG

**Phase 5: Storage**
- Store to MongoDB: hasil_analisis_lengkap
- Store to MySQL: monitoring_kuesioner table
- Status: Mark as PROCESSED

**Phase 6: Notification**
- If sync: Show summary immediately
- If async: Send email when complete

⚠️ **Error Handling**: See 3.2.6.4.2 (RAG failure) and 3.2.6.14.1
(File upload troubleshooting)

📝 **Code Examples**: See 3.2.6.11.1 point 7 (SQL insert queries)

🔧 **Implementation**: 12 detailed points with pseudocode
```

**Improvements:**
- ✅ Clear decision logic (sync vs async)
- ✅ Error handling section referenced
- ✅ Code examples provided
- ✅ Detailed phases with descriptions
- ✅ Clear data storage locations
- ✅ Notification strategy specified

---

## Frequently Asked Questions

### Q: Won't the SRS become too long?

**A:** No, actually shorter:
- **Before**: Separate documents for diagrams + text = fragmented
- **After**: Single comprehensive document (point-based) = more organized

Plus, long diagrams are hard to understand anyway. Point-based is easier to skim.

---

### Q: How do developers know what to implement?

**A:** Each workflow point has:
- **What to do**: The action
- **How to do it**: Pseudocode/SQL
- **When to do it**: Conditional logic
- **Where to do it**: File/function references
- **Why**: Context and rationale

Much clearer than reading a diagram.

---

### Q: What if the workflow changes?

**A:** Update is trivial:
- Open markdown file
- Edit the point that changed
- Commit to git
- Done

No need to redraw diagrams in specialized tools. Plus, you have full git history.

---

### Q: How do we ensure accuracy?

**A:** Each section can be validated against code:

```bash
# Example: Verify SQL query from 3.2.6.11.1 point 7
grep -n "INSERT INTO penugasan_dosen" \
  app/Services/SyncPenugasanService.php
```

Workflow documentation points can be directly traced to code.

---

### Q: Will operations team be able to use this?

**A:** Yes, better than diagrams:
- Section 3.2.6.14: Debugging Guide
- Section 3.2.6.16: Deployment Procedures
- Section 3.2.6.7: Performance Optimization

All operational procedures are documented in natural language with examples.

---

## Summary

### What Changed
✅ Replaced 8-10 sequence diagrams with point-based workflow documentation
✅ Added decision logic, error handling, code examples
✅ Made documentation version-controllable and easy to update
✅ Created operational guidance (debugging, troubleshooting, monitoring)

### What Stayed the Same
✅ System functionality (no code changes required)
✅ Requirements (all requirements still met)
✅ Architecture (same design, better documented)

### What Improved
✅ Documentation accuracy (tied to actual code)
✅ Team communication (clearer procedures)
✅ Operations capability (comprehensive runbooks)
✅ Maintenance effort (easier updates)
✅ Completeness (nothing missing)

---

## Next Actions

### For Product Managers
1. Review 3.2.6 workflow documentation
2. Decide: Update SRS now or wait for next release?
3. If update: Allocate ~20-30 hours for SRS rewrite

### For Development Team
1. Use 3.2.6.11 as implementation guide for new features
2. Reference 3.2.6.13 when making architecture decisions
3. Use 3.2.6.14 when debugging issues

### For Operations Team
1. Review 3.2.6.14 (Troubleshooting)
2. Review 3.2.6.16 (Deployment)
3. Add monitoring from 3.2.6.7 to ops dashboard

---

**Document Status:** ✅ Ready for Integration  
**Date:** 2026-06-15  
**Audience:** SRS Authors, Development Team, Operations Team, Product Managers
