# 0007: UI groups declared once on the object

Status: proposed

## Context
A key's `x-portable-ui.group` was a bare name. Nothing in the document said what the group was: no label, no widget, no order. Each key in a group would have had to repeat that, or a host had to supply it from elsewhere.

## Decision
- **The object declares its groups once** under `x-portable-ui.groups`: a map from group name to a group object.
- **A group object** has `label` (required), `description`, `widget`, `options` and `weight`, and is closed.
- **Each key names its group** by `x-portable-ui.group`, as before.
- **`groups` is allowed wherever `x-portable-ui` is.** The spec says it belongs on the object whose `properties` name the groups.

## Consequences
- A form host reads a group's label, widget and order from the document.
- An unknown member of a group, or a group without a label, makes the schema invalid.
- The dialect does not cross-check names: a `group` that names no declared group is not an error.
- The validators attach no semantics to it; exporters strip it with the other `x-portable-*` annotations.
