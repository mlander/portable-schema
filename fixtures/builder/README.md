# Builder fixtures

Documents a builder produces from a known set of calls. Language-neutral: a builder in any language that offers the same calls writes the same document.

| File | Built by |
|---|---|
| [`display-settings.json`](display-settings.json) | `php/tests/Builder/SchemaTest.php`: the fluent calls and the attribute class `DisplaySettings` both produce it |

Documents are compared with their keys sorted at every level: a builder writes keywords in authoring order, and the order carries no meaning.
