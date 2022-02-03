## Common code standards
- Use PascalCase for naming classes, types
- Use kebab-case for HTML/CSS style names, classes, ids, attributes, etc
- Use whitespace to separate logical units (don't go overboard)
- Prefer semantic naming (ie.: "title" instead of "str", "fics" instead of "items" or "arr")
- Prefer self-documenting code over comments
- Prefer short, pure functions (unless using closures)
- Always use at most 3 operations per assignment, break it down to multiple variables
- Always create utils instead of duplicate code segments
- Always avoid global namespace pollution, monkeypatching
- Always avoid side effects in pure functions (ie logging from map)
- Always avoid inconsequential code (ie using map instead of foreach)
- Always avoid nested loops/if statements (use functions above 3 levels of indentation)


## Frontend code standards
- Use camelCase for naming functions, variables
- Prefer const over let, do not use var (prefer immutability)
- Prefer arrow functions
- Always use null to signify an empty value (instead of undefined)
- Always avoid !important (use selector weighting)


## Backend code standards
- Use snake_case for naming functions, variables
- Prefer assoc arrays over native classes
- Prefer . over + for string concat
- Prefer x:endx blocks over curly notation (ie if-endif)