# Vocabulary

This document covers `portable-check` and `x-portable-ui`; the other keywords follow.

The syntax of every keyword is defined in [`v1/vocab.json`](v1/vocab.json). This document defines their semantics.

## `portable-check`

Named checks: validation logic that is implemented in code, not expressible with keywords. Examples: a value must be unique among stored data, or a reference must point to an existing entity.

```json
"title": {
  "$ref": "portable:label",
  "portable-check": { "drupal:UniqueField": {} }
},
"author": {
  "$ref": "portable:integer",
  "portable-check": { "drupal:ValidReference": {}, "drupal:ReferenceAccess": {} }
}
```

### Syntax

- **A map** from check names to their options.
- **Names are namespaced** by the integration that provides the check: `<namespace>:<name>`, e.g. `drupal:UniqueField`.
- **Options** are a JSON object, validated against the check's manifest.

### Evaluation

For each name, the validator asks for the check's result in this order:

1. **A local callback** registered for the name on the validator, if any. It overrides any provider.
2. **A provider** registered for the name.
3. **Otherwise** the check is reported in `unchecked` with reason `noProvider`.

Results:

- **Errors use the code `check.<name>`** (e.g. `check.drupal:UniqueField`), or sub-codes `check.<name>.<sub>` declared in the manifest.
- **An error may carry a rendered `message`.** Formatters use it instead of the catalog.
- **An error may carry a `location`**: a JSON Pointer relative to the location of the value the check was asked about. The error is reported at that location joined to the one it was asked about, e.g. `/deadline` + `/amount` = `/deadline/amount`. Without it, the error is reported where the check was asked. A `location` must name a value present in the instance. A `location` that is not a JSON Pointer, or names no value present in the instance (past the end of a list, a property the value does not hold), is a failed check: reported in `unchecked` with reason `error`, its errors dropped.
- **A check that could not run** is reported in `unchecked` with a reason (see [`errors.md`](errors.md)). It never counts as passed.

### Check manifests

Each check is described by a manifest, shipped by its integration, e.g. in a site schema dump:

```json
{
  "id": "drupal:UniqueField",
  "options": { "type": "object", "additionalProperties": false },
  "runs": "server",
  "messages": { "check.drupal:UniqueField": { "text": "{label} “{value}” is already taken." } }
}
```

| Key | Meaning |
|---|---|
| `id` | The namespaced name |
| `options` | A Portable Schema for the options. Linting checks schemas against it |
| `runs` | `server`: implemented only where the system's data lives. `both`: implemented in PHP and JS, and then covered by fixtures like keywords |
| `messages` | Source texts for the check's codes, in the [catalog format](../docs/decisions/0001-message-catalog-format.md) |

A local callback replaces a check's implementation, not its manifest: the name, options and codes stay the same.

## `x-portable-ui`

Hints for drawing a form from the schema: which widget to use, its options, and how keys are grouped and ordered. The validators attach no semantics to it, and `Exporter` strips it with the other `x-portable-*` annotations.

```json
{
  "type": "object",
  "properties": {
    "title": { "$ref": "portable:label", "x-portable-ui": { "weight": 0 } },
    "price": { "type": "number", "x-portable-ui": { "group": "pricing", "weight": 1 } },
    "currency": { "type": "string", "x-portable-ui": { "group": "pricing", "weight": 2 } }
  },
  "x-portable-ui": {
    "groups": {
      "pricing": { "label": "Pricing", "widget": "details", "options": { "open": false }, "weight": 10 }
    }
  }
}
```

### Syntax

An object. It is closed: a member not listed here makes the schema invalid.

| Member | Meaning |
|---|---|
| `widget` | The widget that draws the value, in the host's words, e.g. `time` |
| `options` | An object of options for that widget, e.g. a placeholder or help text |
| `fallback` | Widgets to try, in order, when the host does not have `widget` |
| `group` | The name of the group the key belongs to |
| `weight` | A number that orders the key among its siblings and groups; lower comes first |
| `groups` | A map from group name to a group, declared on the object schema whose `properties` name the groups |

A group is a closed object:

| Member | Meaning |
|---|---|
| `label` | Required. The group's label |
| `description` | Text shown with the group |
| `widget` | How the group is drawn, in the host's words, e.g. `fieldset` or `details` |
| `options` | An object of options for that widget, e.g. `{"open": false}` |
| `weight` | A number that orders the group among other groups and ungrouped keys |

### Placement

- **`x-portable-ui` is allowed on any schema.** The members a host reads depend on where it is: `widget`, `options`, `fallback`, `group` and `weight` on the schema of a key, `groups` on the object that holds the keys.
- **A group is declared once,** under `groups` on the object, and each key names it by `group`.
- **The dialect does not cross-check names.** A `group` that names no declared group, or a declared group that no key names, is not a validation error. The host decides how to draw such a key.
