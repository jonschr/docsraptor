## Version 0.3.0

- Scoped front-end search to the active docs collection on single doc and docs taxonomy views.
- Updated search indexing to include docs title, full content, associated docs category names, and collection names.
- Added paginated REST fetching so search can include more than the first 100 docs or category terms.
- Improved live search behavior so results update after the initial docs index finishes loading.
- Added highlighted search-term matches in result titles, category paths, and snippets.
- Escaped rendered search result text and URLs before injecting highlighted result markup.
