# UNUSED TABLES REPORT
Generated: 2026-06-13 07:05:56

⚠️ **POTENTIALLY UNUSED TABLES**

The following tables were not found in the codebase analysis:

- **users**: User authentication and role management
- **kuesioner_uploads**: Questionnaire files and AI analysis
- **embeddings_cache**: Vector embeddings cache
- **jobs**: Laravel queue jobs

**Note:** This doesn't necessarily mean these tables should be dropped. They might be:
- Used in database migrations
- Used by external systems
- Used in dynamic queries not detected by static analysis
- Framework tables (cache, jobs)
