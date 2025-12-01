# Development Constraints

## Access Limitations

**IMPORTANT:** We only have access to the plugin folder:
```
wp-content/plugins/comfort-comm-chatbot/
```

We do NOT have access to:
- WordPress database (can't create custom tables)
- Other WordPress folders
- WordPress admin beyond plugin settings
- Client's full WordPress installation

## Implications

1. **No custom database tables** - Store data as files (JSON/CSV) in the plugin folder
2. **Use WordPress options** - These are available through plugin settings API
3. **All features must be self-contained** within the plugin folder

---

## Documentation Rules

**IMPORTANT:** After implementing any feature, update `ROADMAP.md` with:

1. **Non-Technical Summary** - What it does in plain English
2. **Technical Summary** - Architecture, files changed, key functions
3. **Common Questions** - Both non-tech and tech Q&A

This helps explain the build to stakeholders and technical reviewers.
