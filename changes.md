## Version 0.3.4

- Stabilized the Docs Search block for use on non-doc pages with collection-filtered search.
- Moved the shared search modal out of block wrapper context so overlay styles behave consistently.
- Added optional visible labels for field-style search blocks while allowing empty labels.

## Version 0.3.3

- Added Docs Search block filter data to the outer block wrapper so collection/category filters are easier for the frontend script to resolve.

## Version 0.3.2

- Updated Docs Search block filtering to request category and collection filtered docs directly from the REST API.

## Version 0.3.1

- Ensured the Docs Search block loads fresh frontend assets after adding category and collection filtering.
- Made Docs Search block collection/category filtering more tolerant of REST API term ID formats.

## Version 0.3.0

- Added a Docs Search block for placing the docs search overlay trigger on non-docs pages.
- Added block settings for search field/button display and optional docs category or collection filtering.
- Scoped front-end search to the active docs collection on single doc and docs taxonomy views.
- Updated search indexing to include docs title, full content, associated docs category names, and collection names.
- Added paginated REST fetching so search can include more than the first 100 docs or category terms.
- Improved live search behavior so results update after the initial docs index finishes loading.
- Added highlighted search-term matches in result titles, category paths, and snippets.
- Escaped rendered search result text and URLs before injecting highlighted result markup.
